/**
 * ══════════════════════════════════════════════
 * profile.js — Protected Profile Dashboard
 * jQuery AJAX · localStorage auth · Load & Update profile
 * ══════════════════════════════════════════════
 */
$(function () {
  'use strict';

  const token    = localStorage.getItem('auth_token');
  const userData = JSON.parse(localStorage.getItem('user') || 'null');

  const $loader  = $('#page-loader');
  const $alert   = $('#alert-box');

  // ══════════════════════════════════════
  // Auth Guard — redirect if no token
  // ══════════════════════════════════════
  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  // ══════════════════════════════════════
  // Populate header immediately from cache
  // ══════════════════════════════════════
  if (userData) {
    $('#nav-username').text(userData.username);
    $('#display-username').text(userData.username);
    $('#display-email').text(userData.email);
    $('#profile-avatar').text(userData.username.charAt(0).toUpperCase());
    $('#info-id').text(userData.userId);
    $('#info-username').text(userData.username);
    $('#info-email').text(userData.email);
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

    // Auto-dismiss success alerts after 4s
    if (type === 'success') {
      setTimeout(function () { $alert.slideUp(200); }, 4000);
    }
  }

  // ══════════════════════════════════════
  // Load Profile from Backend
  // ══════════════════════════════════════
  var cachedProfile = {};   // store fetched profile for reset button

  function loadProfile() {
    $.ajax({
      url:      'php/profile.php',
      method:   'GET',
      dataType: 'json',
      headers:  { 'X-Auth-Token': token },

      success: function (res) {
        if (res.success && res.data && res.data.profile) {
          var p = res.data.profile;
          cachedProfile = p;
          populateForm(p);
        }

        // Populate header from server data if available
        if (res.success && res.data && res.data.user) {
          var u = res.data.user;
          $('#nav-username').text(u.username);
          $('#display-username').text(u.username);
          $('#display-email').text(u.email);
          $('#profile-avatar').text(u.username.charAt(0).toUpperCase());
          $('#info-id').text(u.id);
          $('#info-username').text(u.username);
          $('#info-email').text(u.email);
        }
      },

      error: function (xhr) {
        if (xhr.status === 401) {
          // Token expired or invalid — force re-login
          localStorage.clear();
          window.location.href = 'login.html';
          return;
        }
        showAlert('Failed to load profile data.', 'danger');
      },

      complete: function () {
        // Hide page loader
        $loader.addClass('hide');
        setTimeout(function () { $loader.remove(); }, 300);
      }
    });
  }

  function populateForm(p) {
    $('#prof-age').val(p.age || '');
    $('#prof-dob').val(p.dob || '');
    $('#prof-contact').val(p.contact || '');
    $('#prof-address').val(p.address || '');
    $('#prof-bio').val(p.bio || '');
  }

  loadProfile();

  // ══════════════════════════════════════
  // Save Profile (Update)
  // ══════════════════════════════════════
  var $saveBtn   = $('#btn-save');
  var $saveSpn   = $('#save-spinner');

  $('#profile-form').on('submit', function (e) {
    e.preventDefault();

    var profileData = {
      age:     $.trim($('#prof-age').val()),
      dob:     $.trim($('#prof-dob').val()),
      contact: $.trim($('#prof-contact').val()),
      address: $.trim($('#prof-address').val()),
      bio:     $.trim($('#prof-bio').val())
    };

    // ── Optional: basic contact validation ──
    if (profileData.contact && !/^[\d+\-\s()]{7,15}$/.test(profileData.contact)) {
      showAlert('Please enter a valid contact number.', 'warning');
      return;
    }

    $saveBtn.prop('disabled', true);
    $saveSpn.removeClass('d-none');

    $.ajax({
      url:         'php/profile.php',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify(profileData),
      dataType:    'json',
      headers:     { 'X-Auth-Token': token },

      success: function (res) {
        if (res.success) {
          cachedProfile = profileData;    // update cache
          showAlert('<i class="bi bi-check-circle me-1"></i>' + res.message, 'success');
        } else {
          showAlert('<i class="bi bi-exclamation-circle me-1"></i>' + res.message, 'danger');
        }
      },

      error: function (xhr) {
        if (xhr.status === 401) {
          localStorage.clear();
          window.location.href = 'login.html';
          return;
        }
        var msg = 'Failed to save profile.';
        try {
          var body = JSON.parse(xhr.responseText);
          if (body.message) msg = body.message;
        } catch (_) {}
        showAlert('<i class="bi bi-exclamation-triangle me-1"></i>' + msg, 'danger');
      },

      complete: function () {
        $saveBtn.prop('disabled', false);
        $saveSpn.addClass('d-none');
      }
    });
  });

  // ══════════════════════════════════════
  // Reset form to last saved state
  // ══════════════════════════════════════
  $('#btn-reset').on('click', function () {
    populateForm(cachedProfile);
    $alert.slideUp(200);
  });

  // ══════════════════════════════════════
  // Logout
  // ══════════════════════════════════════
  $('#btn-logout').on('click', function () {
    // Destroy Redis session server-side before clearing client storage
    $.ajax({
      url:         'php/login.php',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify({ action: 'logout' }),
      dataType:    'json',
      headers:     { 'X-Auth-Token': token },
      complete:    function () {
        localStorage.clear();
        window.location.href = 'login.html';
      }
    });
  });
});
