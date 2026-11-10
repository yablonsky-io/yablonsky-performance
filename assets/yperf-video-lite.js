/* YPerf supported media delivery: click-to-play YouTube and non-destructive preview fallback. */
(function () {
  'use strict';

  function markMissingPreview(img) {
    var trigger = img.closest('a.yperf-video-lite-trigger');
    if (!trigger) { return; }
    var holder = trigger.closest('.yperf-video-lite');
    if (!holder || holder.classList.contains('yperf-video-lite-no-thumb')) { return; }
    holder.classList.add('yperf-video-lite-no-thumb');
    var label = document.createElement('span');
    label.className = 'yperf-video-lite-missing-label';
    var message = document.createElement('strong');
    var labels = window.YPerfVideoLiteI18n || {};
    message.textContent = labels.previewUnavailable || 'Video preview unavailable';
    var action = document.createElement('span');
    var vimeo = holder.getAttribute('data-yperf-video-provider') === 'vimeo';
    action.textContent = vimeo ? (labels.openOnVimeo || 'Open on Vimeo') : (labels.openOnYoutube || 'Open on YouTube');
    label.appendChild(message);
    label.appendChild(action);
    trigger.appendChild(label);
    // Retain the original link. A missing preview does not prove the video is
    // deleted or permit the optimizer to delete site editorial content.
    trigger.setAttribute('aria-label', (vimeo ? (labels.openOnVimeo || 'Open on Vimeo') : (labels.openOnYoutube || 'Open on YouTube')) + ': ' +
      (holder.getAttribute('data-yperf-video-title') || labels.video || 'Video'));
  }

  function inspectThumb(img) {
    if (!img.complete) { return; }
    // YouTube often responds 200 OK with a generic 120x90 grey JPG even when
    // the requested /hqdefault.jpg thumbnail is unavailable.
    if (img.naturalWidth <= 120 || img.naturalHeight <= 90) {
      markMissingPreview(img);
    }
  }

  function init() {
    var images = document.querySelectorAll('.yperf-video-lite-trigger img');
    for (var i = 0; i < images.length; i++) {
      (function (img) {
        img.addEventListener('load', function () { inspectThumb(img); });
        img.addEventListener('error', function () { markMissingPreview(img); });
        inspectThumb(img);
      }(images[i]));
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, {once: true});
  } else {
    init();
  }

  document.addEventListener('click', function (event) {
    var element = event.target;
    if (!element || typeof element.closest !== 'function') { return; }
    var trigger = element.closest('a.yperf-video-lite-trigger');
    if (!trigger) { return; }
    var holder = trigger.closest('.yperf-video-lite');
    if (!holder || holder.classList.contains('yperf-video-lite-no-thumb')) {
      // Unknown video availability: retain the external YouTube link.
      return;
    }
    var raw = holder.getAttribute('data-yperf-video-embed') || '';
    var url;
    try { url = new URL(raw, location.href); } catch (e) { return; }
    var vimeo = holder.getAttribute('data-yperf-video-provider') === 'vimeo';
    var allowed = vimeo
      ? url.hostname === 'player.vimeo.com' && /^\/video\/[0-9]{1,12}(?:\/|$)/.test(url.pathname)
      : /^(?:www\.)?youtube(?:-nocookie)?\.com$/i.test(url.hostname) &&
        /^\/embed\/[A-Za-z0-9_-]{11}(?:\/|$)/.test(url.pathname);
    if (!allowed || url.protocol !== 'https:' ||
        (vimeo && (url.username || url.password || url.port))) { return; }
    event.preventDefault();
    url.searchParams.set('autoplay', '1');
    var iframe = document.createElement('iframe');
    var localizedLabels = window.YPerfVideoLiteI18n || {};
    iframe.setAttribute('title', holder.getAttribute('data-yperf-video-title') || (vimeo ? (localizedLabels.vimeoVideo || 'Vimeo video') : (localizedLabels.youtubeVideo || 'YouTube video')));
    iframe.setAttribute('allow', vimeo ? 'autoplay; fullscreen; picture-in-picture' :
      'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    iframe.setAttribute('loading', 'eager');
    iframe.src = url.href;
    holder.replaceChildren(iframe);
  }, false);
}());
