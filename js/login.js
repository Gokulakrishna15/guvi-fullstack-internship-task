/**
 * ══════════════════════════════════════════════
 * login.js — Login Form Handler
 * jQuery AJAX · localStorage token storage
 * ══════════════════════════════════════════════
 */
$(function () {
  'use strict';

  const $form       = $('#login-form');
  const $identifier = $('#login-identifier');
  const $password   = $('#login-password');
  const $alert      = $('#alert-box');
  const $spinner    = $('#login-spinner');
  const $btn        = $('#btn-login');

  // ── If already logged in, redirect to profile ──
  if (localStorage.getItem('auth_token')) {
    window.location.href = 'profile.html';
    return;
  }

  // ══════════════════════════════════════
  // Alert Helpers
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

    const identifier = $.trim($identifier.val());
    const password   = $password.val();

    // ── Basic checks ──
    if (!identifier) {
      showAlert('Please enter your email or username.', 'warning');
      $identifier.focus();
      return;
    }
    if (!password) {
      showAlert('Please enter your password.', 'warning');
      $password.focus();
      return;
    }

    // ── Loading state ──
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // ── AJAX POST ──
    $.ajax({
      url:         'php/login.php',
      type:        'POST',
      contentType: 'application/json',
      data:        JSON.stringify({
        identifier: identifier,
        password:   password
      }),
      dataType: 'json',

      success: function (res) {
        if (res.status === 'success') {
          // Store token and user data in localStorage
          localStorage.setItem('auth_token', res.token);
          localStorage.setItem('user', JSON.stringify(res.user));

          showAlert(
            '<i class="bi bi-check-circle me-1"></i>Login successful! Redirecting…',
            'success'
          );

          setTimeout(function () {
            window.location.href = 'profile.html';
          }, 800);
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
