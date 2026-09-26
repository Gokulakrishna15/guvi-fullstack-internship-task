/**
 * ══════════════════════════════════════════════
 * register.js — Registration Form Handler
 * jQuery AJAX · Client-side validation · Password strength
 * ══════════════════════════════════════════════
 */
$(function () {
  'use strict';

  const $form     = $('#register-form');
  const $username = $('#reg-username');
  const $email    = $('#reg-email');
  const $password = $('#reg-password');
  const $confirm  = $('#reg-confirm');
  const $alert    = $('#alert-box');
  const $spinner  = $('#reg-spinner');
  const $btn      = $('#btn-register');
  const $pwBar    = $('#pw-strength-bar');

  // ── If already logged in, redirect to profile ──
  if (localStorage.getItem('auth_token')) {
    window.location.href = 'profile.html';
    return;
  }

  // ══════════════════════════════════════
  // Password Strength Indicator
  // ══════════════════════════════════════
  $password.on('input', function () {
    const val = $(this).val();
    $pwBar.removeClass('weak medium strong');

    if (val.length === 0) {
      $pwBar.css('width', '0');
      return;
    }

    let score = 0;
    if (val.length >= 6)                    score++;
    if (val.length >= 10)                   score++;
    if (/[A-Z]/.test(val))                  score++;
    if (/[0-9]/.test(val))                  score++;
    if (/[^A-Za-z0-9]/.test(val))           score++;

    if (score <= 2) {
      $pwBar.addClass('weak');
    } else if (score <= 3) {
      $pwBar.addClass('medium');
    } else {
      $pwBar.addClass('strong');
    }
  });

  // ══════════════════════════════════════
  // Show / Hide Alert
  // ══════════════════════════════════════
  function showAlert(msg, type) {
    $alert
      .removeClass('alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .html(msg)
      .slideDown(200);
  }

  function hideAlert() {
    $alert.slideUp(200);
  }

  // ══════════════════════════════════════
  // Form Submission via AJAX
  // ══════════════════════════════════════
  $form.on('submit', function (e) {
    e.preventDefault();
    hideAlert();

    const username = $.trim($username.val());
    const email    = $.trim($email.val());
    const password = $password.val();
    const confirm  = $confirm.val();

    // ── Client-side validation ──
    if (username.length < 3) {
      showAlert('Username must be at least 3 characters.', 'warning');
      $username.focus();
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAlert('Please enter a valid email address.', 'warning');
      $email.focus();
      return;
    }

    if (password.length < 6) {
      showAlert('Password must be at least 6 characters.', 'warning');
      $password.focus();
      return;
    }

    if (password !== confirm) {
      showAlert('Passwords do not match.', 'warning');
      $confirm.focus();
      return;
    }

    // ── Show loading state ──
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // ── AJAX POST to PHP ──
    $.ajax({
      url:         'php/register.php',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify({
        username: username,
        email:    email,
        password: password
      }),
      dataType: 'json',

      success: function (res) {
        if (res.success) {
          showAlert(
            '<i class="bi bi-check-circle me-1"></i>' + res.message +
            ' Redirecting to login…',
            'success'
          );
          // Redirect after brief delay
          setTimeout(function () {
            window.location.href = 'login.html';
          }, 1500);
        } else {
          showAlert('<i class="bi bi-exclamation-circle me-1"></i>' + res.message, 'danger');
        }
      },

      error: function (xhr) {
        var msg = 'Server error. Please try again.';
        try {
          var body = JSON.parse(xhr.responseText);
          if (body.message) msg = body.message;
        } catch (_) {}
        showAlert('<i class="bi bi-exclamation-triangle me-1"></i>' + msg, 'danger');
      },

      complete: function () {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none');
      }
    });
  });
});
