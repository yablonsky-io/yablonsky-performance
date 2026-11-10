(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }


    var config = window.YPerfDashboard || {};
    var tabs = document.querySelectorAll('[data-yperf-tab]');
    var panels = document.querySelectorAll('[data-yperf-panel]');
    var form = document.getElementById('yperf-runtime-form');
    var runButton = document.getElementById('yperf-run-runtime');
    var progressWrap = document.getElementById('yperf-runtime-progress');
    var progressBar = document.getElementById('yperf-progress-bar');
    var progressRoot = progressBar ? progressBar.parentElement : null;
    var progressLabel = document.getElementById('yperf-progress-label');
    var progressPercent = document.getElementById('yperf-progress-percent');
    var livePass = document.getElementById('yperf-live-pass');
    var liveFail = document.getElementById('yperf-live-fail');
    var liveSkip = document.getElementById('yperf-live-skip');
    var toast = document.getElementById('yperf-toast');
    var featureModal = document.getElementById('yperf-feature-modal');
    var featureModalContent = document.getElementById('yperf-modal-content');
    var lastModalTrigger = null;
    var wrap = document.querySelector('.yperf-wrap');

    function syncLayoutContext(target) {
        if (!wrap) { return; }
        wrap.classList.toggle('is-secondary-tab', !!target && target !== 'overview');
        wrap.classList.toggle('is-migration-view', target === 'migration');
    }

    function syncTabUrl(target) {
        try {
            var url = new URL(window.location.href);
            if (!target || target === 'overview') {
                url.searchParams.delete('yperf_tab');
            } else {
                url.searchParams.set('yperf_tab', target);
            }
            var section = url.searchParams.get('yperf_section') || '';
            if (section && (!target || section.indexOf(target + ':') !== 0)) {
                url.searchParams.delete('yperf_section');
            }
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-yperf-tab');
            tabs.forEach(function (item) {
                var activeTab = item === tab;
                item.classList.toggle('is-active', activeTab);
                item.setAttribute('aria-selected', activeTab ? 'true' : 'false');
                item.tabIndex = activeTab ? 0 : -1;
            });
            panels.forEach(function (panel) {
                var active = panel.getAttribute('data-yperf-panel') === target;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
            syncTabUrl(target);
            syncLayoutContext(target);
        });
    });

    function moveTabFocus(list, current, event) {
        if (!list.length) { return; }
        var index = list.indexOf(current);
        if (index < 0) { return; }
        var next = index;
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') { next = (index + 1) % list.length; }
        else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') { next = (index - 1 + list.length) % list.length; }
        else if (event.key === 'Home') { next = 0; }
        else if (event.key === 'End') { next = list.length - 1; }
        else { return; }
        event.preventDefault();
        list[next].click();
        list[next].focus();
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('keydown', function (event) {
            moveTabFocus(Array.prototype.slice.call(tabs), tab, event);
        });
    });

    function openTab(name) {
        var targetTab = document.querySelector('[data-yperf-tab="' + name + '"]');
        if (!targetTab) { return; }
        targetTab.click();
        targetTab.focus();
    }

    syncLayoutContext('overview');

    try {
        var currentUrl = new URL(window.location.href);
        var requestedTab = currentUrl.searchParams.get('yperf_tab');
        var requestedSection = currentUrl.searchParams.get('yperf_section');
        if (requestedSection === 'setup:migration') {
            openTab('migration');
            syncTabUrl('migration');
        } else if (requestedTab) {
            openTab(requestedTab);
        } else if (requestedSection && document.querySelector('[data-yperf-subtab="' + requestedSection + '"]')) {
            openTab(requestedSection.split(':')[0]);
        }
        if (currentUrl.searchParams.has('yperf_refresh_token')) {
            currentUrl.searchParams.delete('yperf_refresh_token');
            window.history.replaceState({}, '', currentUrl.toString());
        }
    } catch (e) {}

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-yperf-copy-text]') : null;
        if (!button) { return; }
        var text = button.getAttribute('data-yperf-copy-text') || '';
        if (!text) { return; }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showToast((config.strings && config.strings.copied) || 'Copied.');
            }).catch(function () {
                showToast((config.strings && config.strings.copyFailed) || 'Could not copy automatically.', true);
            });
            return;
        }
        var temp = document.createElement('textarea');
        temp.value = text;
        temp.setAttribute('readonly', 'readonly');
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            showToast((config.strings && config.strings.copied) || 'Copied.');
        } catch (e) {
            showToast((config.strings && config.strings.copyFailed) || 'Could not copy automatically.', true);
        }
        document.body.removeChild(temp);
    });

    var hostSnapshotFile = document.getElementById('yperf-host-snapshot-file');
    var hostSnapshotJson = document.getElementById('yperf-host-snapshot-json');
    if (hostSnapshotFile && hostSnapshotJson && window.FileReader) {
        hostSnapshotFile.addEventListener('change', function () {
            var file = hostSnapshotFile.files && hostSnapshotFile.files[0] ? hostSnapshotFile.files[0] : null;
            if (!file) { return; }
            if (file.size > 524288) {
                showToast((config.strings && config.strings.hostSnapshotTooLarge) || 'Host snapshot is larger than the 512 KB safety limit.', true);
                hostSnapshotFile.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function () { hostSnapshotJson.value = typeof reader.result === 'string' ? reader.result : ''; };
            reader.onerror = function () { showToast((config.strings && config.strings.hostSnapshotReadError) || 'Could not read the selected host snapshot.', true); };
            reader.readAsText(file);
        });
    }

    function openFeature(id, trigger) {
        if (!featureModal || !featureModalContent) { return; }
        var template = document.getElementById('yperf-feature-template-' + id);
        if (!template) { return; }
        lastModalTrigger = trigger || null;
        featureModalContent.innerHTML = '';
        featureModalContent.appendChild(template.content.cloneNode(true));
        featureModal.hidden = false;
        featureModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('yperf-modal-open');
        var closeButton = featureModal.querySelector('.yperf-modal-close');
        if (closeButton) { closeButton.focus(); }
    }

    function closeFeature() {
        if (!featureModal) { return; }
        featureModal.hidden = true;
        featureModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('yperf-modal-open');
        if (lastModalTrigger && typeof lastModalTrigger.focus === 'function') { lastModalTrigger.focus(); }
        lastModalTrigger = null;
    }

    function activateFeatureFilter(filter) {
        document.querySelectorAll('[data-yperf-feature-filter]').forEach(function (button) {
            button.classList.toggle('is-active', button === filter);
        });
        var value = filter.getAttribute('data-yperf-feature-filter') || 'all';
        document.querySelectorAll('[data-yperf-feature-group]').forEach(function (tile) {
            tile.hidden = value !== 'all' && tile.getAttribute('data-yperf-feature-group') !== value;
        });
    }

    function post(data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (key) { body.append(key, data[key]); });
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok || !json || !json.success) {
                    var message = json && json.data && json.data.message ? json.data.message : (config.strings && config.strings.ajaxError ? config.strings.ajaxError : yperfI18n('requestFailed', 'Request failed.'));
                    throw new Error(message);
                }
                return json.data;
            });
        });
    }

    function setProgress(done, total, label) {
        var percent = total > 0 ? Math.round((done / total) * 100) : 0;
        if (progressBar) { progressBar.style.width = percent + '%'; }
        if (progressPercent) { progressPercent.textContent = percent + '%'; }
        if (progressLabel) { progressLabel.textContent = label || ''; }
        if (progressRoot) { progressRoot.setAttribute('aria-valuenow', String(percent)); }
    }

    function runtimeCooldown(test) {
        var originMs = test && Number(test.origin_ms || 0);
        var elapsedMs = test && Number(test.ms || 0);
        var observedMs = Math.max(originMs || 0, elapsedMs || 0);
        if (observedMs < 5000) { return Promise.resolve(); }
        var delayMs = observedMs >= 10000 ? 1500 : 750;
        return new Promise(function (resolve) { window.setTimeout(resolve, delayMs); });
    }

    function updateLiveCounters(tests) {
        var counts = {pass: 0, fail: 0, skip: 0};
        tests.forEach(function (test) {
            if (test && counts.hasOwnProperty(test.status)) { counts[test.status] += 1; }
        });
        if (livePass) { livePass.textContent = String(counts.pass); }
        if (liveFail) { liveFail.textContent = String(counts.fail); }
        if (liveSkip) { liveSkip.textContent = String(counts.skip); }
    }

    async function runAcceptance(event) {
        if (!form || !runButton || !Array.isArray(config.steps)) { return; }
        event.preventDefault();
        var tests = [];
        var total = config.steps.length;
        runButton.disabled = true;
        if (progressWrap) { progressWrap.hidden = false; }
        updateLiveCounters(tests);
        setProgress(0, total, config.strings && config.strings.starting ? config.strings.starting : 'Preparing checks…');

        try {
            for (var i = 0; i < total; i += 1) {
                var step = config.steps[i];
                var label = (config.strings && config.strings.running ? config.strings.running : 'Running') + ' ' + (i + 1) + '/' + total + ' · ' + step.label;
                setProgress(i, total, label);
                var result = await post({
                    action: 'yperf_runtime_acceptance_step',
                    _ajax_nonce: config.nonce,
                    step: step.id
                });
                if (result && result.test) { tests.push(result.test); }
                updateLiveCounters(tests);
                setProgress(i + 1, total, label);
                if (result && result.test) { await runtimeCooldown(result.test); }
            }

            setProgress(total, total, config.strings && config.strings.finishing ? config.strings.finishing : 'Saving report…');
            await post({
                action: 'yperf_runtime_acceptance_finish',
                _ajax_nonce: config.nonce,
                tests: JSON.stringify(tests)
            });
            setProgress(total, total, config.strings && config.strings.completed ? config.strings.completed : 'Runtime Acceptance complete.');
            window.setTimeout(function () { window.location.assign(window.location.href); }, 500);
        } catch (error) {
            runButton.disabled = false;
            showToast(error && error.message ? error.message : (config.strings && config.strings.ajaxError ? config.strings.ajaxError : yperfI18n('runtimeStopped', 'Runtime Acceptance stopped.')), true);
            if (progressLabel) { progressLabel.textContent = config.strings && config.strings.ajaxError ? config.strings.ajaxError : yperfI18n('runtimeStopped', 'Runtime Acceptance stopped.'); }
        }
    }

    if (form) { form.addEventListener('submit', runAcceptance); }
    function getReportText() {
        var node = document.getElementById('yperf-runtime-report-data');
        if (!node) { return ''; }
        try {
            return JSON.stringify(JSON.parse(node.value || '{}'), null, 2);
        } catch (e) {
            return node.value || '';
        }
    }

    function reportToTxt(jsonText) {
        try {
            var report = JSON.parse(jsonText);
            var lines = [];
            lines.push(yperfI18n('reportTitle', 'Yablonsky Performance — Runtime Acceptance'));
            lines.push(yperfI18n('reportSite', 'Site: ') + (report.site || ''));
            lines.push(yperfI18n('reportVersion', 'Version: ') + (report.version || ''));
            if (report.summary) {
                lines.push(yperfI18n('reportSummary', 'Summary: ') + (report.summary.pass || 0) + yperfI18n('reportPassed', ' passed / ') + (report.summary.fail || 0) + yperfI18n('reportFailed', ' failed / ') + (report.summary.skip || 0) + yperfI18n('reportSkipped', ' skipped / ') + (report.summary.attention || 0) + yperfI18n('reportAttention', ' attention'));
            }
            lines.push('');
            (report.tests || []).forEach(function (test) {
                var meta = [];
                if (test.http_code) { meta.push('HTTP ' + test.http_code); }
                if (typeof test.ms !== 'undefined') { meta.push(test.ms + ' ms'); }
                lines.push('[' + String(test.status || '').toUpperCase() + '] ' + (test.name || '') + (meta.length ? ' — ' + meta.join(' · ') : ''));
                if (test.url) { lines.push(yperfI18n('reportUrlLabel', '  URL: ') + test.url); }
                if (test.details) { lines.push('  ' + test.details); }
                if (test.attention_note) { lines.push(yperfI18n('reportAttentionLabel', '  Attention: ') + test.attention_note); }
                if (test.cache_signals) { lines.push(yperfI18n('reportCacheSignals', '  Cache signals: ') + JSON.stringify(test.cache_signals)); }
            });
            return lines.join('\n');
        } catch (e) {
            return jsonText;
        }
    }

    // Export a bounded, privacy-minimized origin-cost view of the saved report.
    // This never starts another probe and never includes raw SQL or caller paths.
    function reportToSqlJson(jsonText) {
        var report = JSON.parse(jsonText);
        var routes = [];
        (report.tests || []).forEach(function (test) {
            var metrics = test && test.origin_metrics;
            if (!metrics || metrics.state !== 'measured') { return; }
            routes.push({
                route: String(test.name || ''),
                query_count: Number(metrics.query_count || 0),
                db_time_ms: Number(metrics.db_time_ms || 0),
                max_query_ms: Number(metrics.max_query_ms || 0),
                php_elapsed_ms: Number(metrics.php_elapsed_ms || 0),
                slow_queries_100ms: Number(metrics.slow_queries_100ms || 0),
                sql_calc_found_rows_count: Number(metrics.sql_calc_found_rows_count || 0),
                hotspots: (metrics.hotspots || []).map(function (hotspot) {
                    return {
                        signature_sha256: String(hotspot.signature_sha256 || ''),
                        family: String(hotspot.family || ''),
                        caller_class: String(hotspot.caller_class || ''),
                        count: Number(hotspot.count || 0),
                        total_ms: Number(hotspot.total_ms || 0),
                        max_ms: Number(hotspot.max_ms || 0),
                        found_rows: !!hotspot.found_rows,
                        order_by: !!hotspot.order_by,
                        group_by: !!hotspot.group_by,
                        joins: !!hotspot.joins,
                        has_limit: !!hotspot.has_limit
                    };
                })
            });
        });
        return JSON.stringify({
            schema: 1,
            source: 'Yablonsky Performance / saved Runtime Acceptance',
            version: String(report.version || ''),
            generated_at: report.generated_at || null,
            origin_cost_summary: report.origin_cost_summary || {},
            routes: routes,
            raw_sql_exported: false
        }, null, 2);
    }

    function download(content, type, extension, prefix) {
        var blob = new Blob([content], {type: type});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        var stamp = new Date().toISOString().replace(/[:.]/g, '-');
        link.href = url;
        link.download = (prefix || 'yperf-runtime-acceptance') + '-' + stamp + '.' + extension;
        document.body.appendChild(link);
        link.click();
        link.remove();
        // Browsers may dispatch the download asynchronously. Releasing the
        // Blob URL in the same task can cancel the SQL JSON download.
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 30000);
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', 'readonly');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
            } catch (e) {
                reject(e);
            }
            area.remove();
        });
    }

    function showToast(message, isError, timeout) {
        if (!toast) { return; }
        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.hidden = false;
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(function () { toast.hidden = true; }, timeout || 2200);
    }

    try {
        if (window.sessionStorage && window.sessionStorage.getItem('yperfEvidenceRefreshComplete') === '1') {
            window.sessionStorage.removeItem('yperfEvidenceRefreshComplete');
            showToast((config.strings && config.strings.evidenceCompletedDetail) || 'Evidence refresh complete — Environment, Runtime Acceptance and Benchmark are current.', false, 4800);
        }
    } catch (e) {}

    document.addEventListener('click', function (event) {
        var feature = event.target.closest('[data-yperf-feature]');
        if (feature) {
            openFeature(feature.getAttribute('data-yperf-feature'), feature);
            return;
        }
        var sectionOpener = event.target.closest('[data-yperf-open-section]');
        if (sectionOpener) {
            var sectionKey = sectionOpener.getAttribute('data-yperf-open-section') || '';
            if (sectionKey === 'setup:migration') {
                openTab('migration');
                return;
            }
            var sectionParts = sectionKey.split(':');
            if (sectionParts.length > 1) {
                openTab(sectionParts[0]);
                var sectionButton = document.querySelector('[data-yperf-subtab="' + sectionKey + '"]');
                if (sectionButton) { sectionButton.click(); }
            }
            return;
        }
        var migrationTechnicalOpener = event.target.closest('[data-yperf-open-migration-technical]');
        if (migrationTechnicalOpener) {
            openTab('migration');
            var technical = document.getElementById('yperf-migration-technical');
            if (technical) {
                technical.open = true;
                window.setTimeout(function () {
                    try { technical.scrollIntoView({behavior: 'smooth', block: 'start'}); } catch (e) { technical.scrollIntoView(); }
                }, 40);
            }
            return;
        }
        var tabOpener = event.target.closest('[data-yperf-open-tab]');
        if (tabOpener) {
            openTab(tabOpener.getAttribute('data-yperf-open-tab'));
            var requestedSubtab = tabOpener.getAttribute('data-yperf-open-subtab');
            if (requestedSubtab) {
                var subtabButton = document.querySelector('[data-yperf-subtab="' + requestedSubtab + '"]');
                if (subtabButton) { subtabButton.click(); }
            }
            return;
        }
        var filter = event.target.closest('[data-yperf-feature-filter]');
        if (filter) {
            activateFeatureFilter(filter);
            return;
        }
        if (event.target.closest('[data-yperf-close-modal]')) {
            closeFeature();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && featureModal && !featureModal.hidden) {
            closeFeature();
            return;
        }
        if ((event.key === 'Enter' || event.key === ' ') && event.target && event.target.matches('.yperf-kpi-clickable')) {
            event.preventDefault();
            if (event.target.hasAttribute('data-yperf-feature')) {
                openFeature(event.target.getAttribute('data-yperf-feature'), event.target);
            } else if (event.target.hasAttribute('data-yperf-open-tab')) {
                openTab(event.target.getAttribute('data-yperf-open-tab'));
            }
        }
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-yperf-action]');
        if (!button) { return; }
        var action = button.getAttribute('data-yperf-action');
        var jsonText = getReportText();
        if (!jsonText) {
            showToast((config.strings && config.strings.ajaxError) || 'Saved report is unavailable.', true);
            return;
        }
        if (action === 'copy-report') {
            copyText(reportToTxt(jsonText)).then(function () {
                showToast(config.strings && config.strings.copied ? config.strings.copied : 'Report copied.', false);
            }).catch(function () {
                showToast(config.strings && config.strings.copyFailed ? config.strings.copyFailed : 'Could not copy automatically.', true);
            });
        } else if (action === 'download-json') {
            download(jsonText, 'application/json;charset=utf-8', 'json', 'yperf-runtime-acceptance');
        } else if (action === 'download-sql-json') {
            try {
                download(reportToSqlJson(jsonText), 'application/json;charset=utf-8', 'json', 'yperf-sql-hotspots');
            } catch (e) {
                showToast(yperfI18n('sqlExportUnavailable', 'SQL export is unavailable for this saved report.'), true);
            }
        } else if (action === 'download-txt') {
            download(reportToTxt(jsonText), 'text/plain;charset=utf-8', 'txt', 'yperf-runtime-acceptance');
        }
    });
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-yperf-intelligence-action]');
        if (!button) { return; }
        var node = document.getElementById('yperf-intelligence-report-data');
        if (!node || !node.value) { return; }
        var action = button.getAttribute('data-yperf-intelligence-action');
        var text = node.value;
        try { text = JSON.stringify(JSON.parse(text), null, 2); } catch (e) {}
        if (action === 'copy') {
            copyText(text).then(function () {
                showToast(config.strings && config.strings.intelligenceCopied ? config.strings.intelligenceCopied : 'Performance Intelligence report copied.', false);
            }).catch(function () {
                showToast(config.strings && config.strings.copyFailed ? config.strings.copyFailed : 'Could not copy automatically.', true);
            });
        } else if (action === 'download-json') {
            download(text, 'application/json;charset=utf-8', 'json', 'yperf-performance-intelligence');
        }
    });



    var benchmarkForm = document.getElementById('yperf-benchmark-form');
    var benchmarkButton = document.getElementById('yperf-run-benchmark');
    var benchmarkWrap = document.getElementById('yperf-benchmark-progress');
    var benchmarkBar = document.getElementById('yperf-benchmark-bar');
    var benchmarkRoot = benchmarkBar ? benchmarkBar.parentElement : null;
    var benchmarkLabel = document.getElementById('yperf-benchmark-label');
    var benchmarkPercent = document.getElementById('yperf-benchmark-percent');

    function setBenchmarkProgress(done, total, label) {
        var percent = total > 0 ? Math.round((done / total) * 100) : 0;
        if (benchmarkBar) { benchmarkBar.style.width = percent + '%'; }
        if (benchmarkPercent) { benchmarkPercent.textContent = percent + '%'; }
        if (benchmarkLabel) { benchmarkLabel.textContent = label || ''; }
        if (benchmarkRoot) { benchmarkRoot.setAttribute('aria-valuenow', String(percent)); }
    }

    async function runBenchmark(event) {
        if (!benchmarkForm || !benchmarkButton || !Array.isArray(config.benchmarkSteps)) { return; }
        event.preventDefault();
        var results = [];
        var total = config.benchmarkSteps.length;
        benchmarkButton.disabled = true;
        if (benchmarkWrap) { benchmarkWrap.hidden = false; }
        setBenchmarkProgress(0, total, 'Preparing benchmark…');
        try {
            for (var i = 0; i < total; i += 1) {
                var step = config.benchmarkSteps[i];
                var label = 'Running ' + (i + 1) + '/' + total + ' · ' + step.label;
                setBenchmarkProgress(i, total, label);
                var data = await post({action: 'yperf_benchmark_step', _ajax_nonce: config.benchmarkNonce, step: step.id});
                if (data && data.result) { results.push(data.result); }
                setBenchmarkProgress(i + 1, total, label);
            }
            await post({action: 'yperf_benchmark_finish', _ajax_nonce: config.benchmarkNonce, results: JSON.stringify(results)});
            setBenchmarkProgress(total, total, config.strings && config.strings.benchmarkCompleted ? config.strings.benchmarkCompleted : 'Benchmark complete.');
            window.setTimeout(function () { window.location.assign(window.location.href); }, 500);
        } catch (error) {
            benchmarkButton.disabled = false;
            showToast(error && error.message ? error.message : yperfI18n('benchmarkStopped', 'Benchmark stopped.'), true);
        }
    }
    if (benchmarkForm) { benchmarkForm.addEventListener('submit', runBenchmark); }

    var evidenceButtons = Array.prototype.slice.call(document.querySelectorAll('#yperf-refresh-evidence, [data-yperf-refresh-evidence]'));
    var evidenceButton = evidenceButtons.length ? evidenceButtons[0] : null;
    var evidenceWrap = document.getElementById('yperf-evidence-refresh-progress');
    var evidenceBar = document.getElementById('yperf-evidence-refresh-bar');
    var evidenceRoot = evidenceBar ? evidenceBar.parentElement : null;
    var evidenceLabel = document.getElementById('yperf-evidence-refresh-label');
    var evidencePercent = document.getElementById('yperf-evidence-refresh-percent');
    var evidencePhases = document.querySelectorAll('[data-yperf-evidence-phase]');

    function evidencePhaseNodes() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-yperf-evidence-phase], [data-yperf-evidence-local-phase]'));
    }

    function setEvidencePhase(name, state) {
        evidencePhaseNodes().forEach(function (phase) {
            var phaseName = phase.getAttribute('data-yperf-evidence-phase') || phase.getAttribute('data-yperf-evidence-local-phase');
            if (phaseName !== name) { return; }
            phase.classList.remove('is-active', 'is-complete', 'is-error');
            if (state) { phase.classList.add('is-' + state); }
            var icon = phase.querySelector('span');
            if (icon) { icon.textContent = state === 'complete' ? '✓' : (state === 'error' ? '!' : (state === 'active' ? '•' : '○')); }
        });
    }

    function resetEvidencePhases() {
        evidencePhaseNodes().forEach(function (phase) {
            phase.classList.remove('is-active', 'is-complete', 'is-error');
            var icon = phase.querySelector('span');
            if (icon) { icon.textContent = '○'; }
        });
    }

    function setEvidenceProgress(done, total, label) {
        var percent = total > 0 ? Math.round((done / total) * 100) : 0;
        if (evidenceBar) { evidenceBar.style.width = percent + '%'; }
        if (evidencePercent) { evidencePercent.textContent = percent + '%'; }
        if (evidenceLabel) { evidenceLabel.textContent = label || ''; }
        if (evidenceRoot) { evidenceRoot.setAttribute('aria-valuenow', String(percent)); }
        document.querySelectorAll('[data-yperf-evidence-local]').forEach(function (wrap) {
            var bar = wrap.querySelector('[data-yperf-local-bar]');
            var pct = wrap.querySelector('[data-yperf-local-percent]');
            var text = wrap.querySelector('[data-yperf-local-label]');
            var root = bar ? bar.parentElement : null;
            if (bar) { bar.style.width = percent + '%'; }
            if (pct) { pct.textContent = percent + '%'; }
            if (text) { text.textContent = label || ''; }
            if (root) { root.setAttribute('aria-valuenow', String(percent)); }
        });
        document.querySelectorAll('[data-yperf-companion-bar]').forEach(function (bar) { bar.style.width = percent + '%'; });
        document.querySelectorAll('[data-yperf-companion-percent]').forEach(function (node) { node.textContent = percent + '%'; });
        document.querySelectorAll('[data-yperf-companion-status]').forEach(function (node) { node.textContent = label || ''; });
    }

    async function refreshMigrationFragments() {
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('yperf_fragment_token', String(Date.now()));
            var response = await fetch(url.toString(), {credentials: 'same-origin', cache: 'no-store'});
            if (!response.ok) { return false; }
            var html = await response.text();
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var incomingMigration = doc.querySelector('[data-yperf-panel="migration"]');
            var currentMigration = document.querySelector('[data-yperf-panel="migration"]');
            if (incomingMigration && currentMigration) {
                currentMigration.innerHTML = incomingMigration.innerHTML;
            }
            var incomingReadiness = doc.querySelector('.yperf-site-readiness');
            var currentReadiness = document.querySelector('.yperf-site-readiness');
            if (incomingReadiness && currentReadiness) {
                currentReadiness.replaceWith(incomingReadiness);
            }
            if (currentMigration) {
                currentMigration.hidden = false;
                currentMigration.classList.add('is-active');
            }
            return true;
        } catch (e) {
            return false;
        }
    }
    window.YPerfRefreshMigrationFragments = refreshMigrationFragments;

    async function runEvidenceRefresh(event) {
        if (!evidenceButtons.length || !Array.isArray(config.steps) || !Array.isArray(config.benchmarkSteps)) { return; }
        event.preventDefault();
        var migrationMode = !!(event.currentTarget && event.currentTarget.closest('[data-yperf-migration-step="2"]'));
        if (migrationMode) {
            document.querySelectorAll('[data-yperf-evidence-local]').forEach(function (wrap) { wrap.hidden = false; wrap.classList.add('is-running'); });
        }
        var runtimeTests = [];
        var benchmarkResults = [];
        var total = 1 + config.steps.length + config.benchmarkSteps.length;
        var done = 0;
        evidenceButtons.forEach(function (button) { button.disabled = true; });
        if (runButton) { runButton.disabled = true; }
        if (benchmarkButton) { benchmarkButton.disabled = true; }
        if (evidenceWrap) { evidenceWrap.hidden = false; evidenceWrap.classList.add('is-running'); evidenceWrap.classList.remove('is-complete', 'is-error'); }
        resetEvidencePhases();
        setEvidenceProgress(0, total, (config.strings && config.strings.evidenceStarting) || 'Preparing read-only evidence refresh…');

        try {
            setEvidencePhase('environment', 'active');
            setEvidenceProgress(done, total, (config.strings && config.strings.evidenceEnvironment) || 'Refreshing Environment Check…');
            var environmentSaved = await post({action: 'yperf_environment_refresh', _ajax_nonce: config.environmentNonce});
            if (!environmentSaved || !environmentSaved.report || !environmentSaved.report.generated_at) {
                throw new Error(yperfI18n('environmentEvidenceNotSaved', 'Environment evidence was not saved.'));
            }
            done += 1;
            setEvidencePhase('environment', 'complete');
            setEvidencePhase('runtime', 'active');
            setEvidenceProgress(done, total, (config.strings && config.strings.evidenceEnvironment) || yperfI18n('environmentRefreshed', 'Environment Check refreshed.'));

            for (var i = 0; i < config.steps.length; i += 1) {
                var runtimeStep = config.steps[i];
                var runtimeLabel = ((config.strings && config.strings.evidenceRuntime) || 'Refreshing Runtime Acceptance') + ' ' + (i + 1) + '/' + config.steps.length + ' · ' + runtimeStep.label;
                setEvidenceProgress(done, total, runtimeLabel);
                var runtimeData = await post({action: 'yperf_runtime_acceptance_step', _ajax_nonce: config.nonce, step: runtimeStep.id});
                if (runtimeData && runtimeData.test) { runtimeTests.push(runtimeData.test); }
                done += 1;
                setEvidenceProgress(done, total, runtimeLabel);
                if (runtimeData && runtimeData.test) { await runtimeCooldown(runtimeData.test); }
            }
            var runtimeSaved = await post({action: 'yperf_runtime_acceptance_finish', _ajax_nonce: config.nonce, tests: JSON.stringify(runtimeTests)});
            if (!runtimeSaved || !runtimeSaved.report || runtimeSaved.report.version !== config.version) {
                throw new Error(yperfI18n('runtimeEvidenceNotSaved', 'Runtime Acceptance did not save current-version evidence.'));
            }
            setEvidencePhase('runtime', 'complete');
            setEvidencePhase('benchmark', 'active');

            for (var j = 0; j < config.benchmarkSteps.length; j += 1) {
                var benchmarkStep = config.benchmarkSteps[j];
                var benchmarkLabelText = ((config.strings && config.strings.evidenceBenchmark) || 'Refreshing Performance Benchmark') + ' ' + (j + 1) + '/' + config.benchmarkSteps.length + ' · ' + benchmarkStep.label;
                setEvidenceProgress(done, total, benchmarkLabelText);
                var benchmarkData = await post({action: 'yperf_benchmark_step', _ajax_nonce: config.benchmarkNonce, step: benchmarkStep.id});
                if (benchmarkData && benchmarkData.result) { benchmarkResults.push(benchmarkData.result); }
                done += 1;
                setEvidenceProgress(done, total, benchmarkLabelText);
            }
            var benchmarkSaved = await post({action: 'yperf_benchmark_finish', _ajax_nonce: config.benchmarkNonce, results: JSON.stringify(benchmarkResults)});
            if (!benchmarkSaved || !benchmarkSaved.report || benchmarkSaved.report.version !== config.version) {
                throw new Error(yperfI18n('benchmarkEvidenceNotSaved', 'Performance Benchmark did not save current-version evidence.'));
            }
            setEvidencePhase('benchmark', 'complete');
            if (evidenceWrap) { evidenceWrap.classList.remove('is-running'); evidenceWrap.classList.add('is-complete'); }
            setEvidenceProgress(total, total, (config.strings && config.strings.evidenceCompletedDetail) || 'Evidence refresh complete — Environment, Runtime Acceptance and Benchmark are current.');
            try { if (window.sessionStorage) { window.sessionStorage.setItem('yperfEvidenceRefreshComplete', '1'); } } catch (e) {}
            if (migrationMode) {
                document.querySelectorAll('[data-yperf-evidence-local]').forEach(function (wrap) { wrap.classList.remove('is-running'); wrap.classList.add('is-complete'); });
                window.setTimeout(function () {
                    refreshMigrationFragments().then(function () {
                        showToast((config.strings && config.strings.evidenceCompletedDetail) || 'Evidence refresh complete — Environment, Runtime Acceptance and Benchmark are current.', false);
                    });
                }, 850);
            } else {
                window.setTimeout(function () {
                    try {
                        var reloadUrl = new URL(window.location.href);
                        reloadUrl.searchParams.set('yperf_refresh_token', String(Date.now()));
                        window.location.replace(reloadUrl.toString());
                    } catch (e) {
                        window.location.reload();
                    }
                }, 1400);
            }
        } catch (error) {
            if (evidenceWrap) { evidenceWrap.classList.remove('is-running'); evidenceWrap.classList.add('is-error'); }
            evidencePhases.forEach(function (phase) { if (phase.classList.contains('is-active')) { setEvidencePhase(phase.getAttribute('data-yperf-evidence-phase') || '', 'error'); } });
            evidenceButtons.forEach(function (button) { button.disabled = false; });
            if (runButton) { runButton.disabled = false; }
            if (benchmarkButton) { benchmarkButton.disabled = false; }
            showToast(error && error.message ? error.message : ((config.strings && config.strings.ajaxError) || yperfI18n('evidenceStopped', 'Evidence refresh stopped.')), true);
            if (evidenceLabel) { evidenceLabel.textContent = error && error.message ? error.message : yperfI18n('evidenceStopped', 'Evidence refresh stopped.'); }
        }
    }

    evidenceButtons.forEach(function (button) { button.addEventListener('click', runEvidenceRefresh); });

    var prewarmForm = document.getElementById('yperf-prewarm-form');
    var prewarmButton = document.getElementById('yperf-run-prewarm');
    var prewarmWrap = document.getElementById('yperf-prewarm-progress');
    var prewarmBar = document.getElementById('yperf-prewarm-bar');
    var prewarmRoot = prewarmBar ? prewarmBar.parentElement : null;
    var prewarmLabel = document.getElementById('yperf-prewarm-label');
    var prewarmPercent = document.getElementById('yperf-prewarm-percent');

    function setPrewarmProgress(done, total, label) {
        var percent = total > 0 ? Math.round((done / total) * 100) : 0;
        if (prewarmBar) { prewarmBar.style.width = percent + '%'; }
        if (prewarmPercent) { prewarmPercent.textContent = percent + '%'; }
        if (prewarmLabel) { prewarmLabel.textContent = label || ''; }
        if (prewarmRoot) { prewarmRoot.setAttribute('aria-valuenow', String(percent)); }
    }

    async function performBoundedPrewarm(options) {
        options = options && typeof options === 'object' ? options : {};
        if (!Array.isArray(config.prewarmUrls) || !config.prewarmNonce) { return []; }
        var requestedLimit = parseInt(options.limit || 12, 10);
        var limit = Math.max(1, Math.min(12, isNaN(requestedLimit) ? 12 : requestedLimit));
        var urls = config.prewarmUrls.slice(0, limit);
        var results = [];
        var total = urls.length;
        var silent = !!options.silent;
        var onProgress = typeof options.onProgress === 'function' ? options.onProgress : null;
        if (!silent && prewarmButton) { prewarmButton.disabled = true; }
        if (!silent && prewarmWrap) { prewarmWrap.hidden = false; }
        if (!silent) { setPrewarmProgress(0, total, config.strings && config.strings.prewarmStarting ? config.strings.prewarmStarting : 'Preparing URLs…'); }
        if (onProgress) { onProgress(0, total); }
        try {
            for (var i = 0; i < total; i += 1) {
                var label = 'Warming ' + (i + 1) + '/' + total;
                if (!silent) { setPrewarmProgress(i, total, label); }
                var data = await post({action: 'yperf_prewarm_step', _ajax_nonce: config.prewarmNonce, url: urls[i]});
                if (data && data.result) { results.push(data.result); }
                if (!silent) { setPrewarmProgress(i + 1, total, label); }
                if (onProgress) { onProgress(i + 1, total); }
            }
            await post({action: 'yperf_prewarm_finish', _ajax_nonce: config.prewarmNonce, results: JSON.stringify(results)});
            if (!silent) { setPrewarmProgress(total, total, config.strings && config.strings.prewarmCompleted ? config.strings.prewarmCompleted : 'Prewarm complete.'); }
            if (!silent && prewarmButton) { prewarmButton.disabled = false; }
            return results;
        } catch (error) {
            if (!silent && prewarmButton) { prewarmButton.disabled = false; }
            throw error;
        }
    }

    async function runPrewarm(event) {
        if (!prewarmForm || !Array.isArray(config.prewarmUrls)) { return; }
        if (event && typeof event.preventDefault === 'function') { event.preventDefault(); }
        try {
            await performBoundedPrewarm();
            window.setTimeout(function () { window.location.assign(window.location.href); }, 500);
        } catch (error) {
            showToast(error && error.message ? error.message : yperfI18n('prewarmStopped', 'Prewarm stopped.'), true);
        }
    }

    // YPerf 2.20.0 — expose the same bounded, verified prewarm runner so
    // Optimize Now can finish with a hot-start pass without duplicating logic.
    window.yperfRunBoundedPrewarm = performBoundedPrewarm;
    if (prewarmForm) { prewarmForm.addEventListener('submit', runPrewarm); }

}());

/* 1.4.0 — exact plan preview. */
document.addEventListener('change', function (event) {
    var input = event.target;
    if (!input || input.name !== 'yperf_performance_profile') { return; }
    var value = input.value || 'observe';
    document.querySelectorAll('[data-yperf-plan-preview]').forEach(function (preview) {
        preview.hidden = preview.getAttribute('data-yperf-plan-preview') !== value;
    });
    document.querySelectorAll('.yperf-profile-card').forEach(function (card) {
        var radio = card.querySelector('input[name="yperf_performance_profile"]');
        card.classList.toggle('is-selected', !!radio && radio.checked);
    });
});

/* YPerf 1.42.0 — progressive disclosure for dashboard sections. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    function sectionKey(button) { return button.getAttribute('data-yperf-subtab') || ''; }
    function activateSection(key, syncUrl) {
        if (!key || key.indexOf(':') < 0) { return; }
        var parts = key.split(':');
        var panelName = parts.shift();
        var panel = document.querySelector('[data-yperf-panel="' + panelName + '"]');
        if (!panel) { return; }
        panel.querySelectorAll('[data-yperf-subtab]').forEach(function (button) {
            var active = sectionKey(button) === key;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.tabIndex = active ? 0 : -1;
        });
        panel.querySelectorAll('[data-yperf-subpanel]').forEach(function (subpanel) {
            var active = subpanel.getAttribute('data-yperf-subpanel') === key;
            subpanel.hidden = !active;
            subpanel.classList.toggle('is-active', active);
        });
        if (syncUrl) {
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('yperf_section', key);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        }
    }
    document.querySelectorAll('[data-yperf-subtab]').forEach(function (button) {
        button.addEventListener('click', function () { activateSection(sectionKey(button), true); });
        button.addEventListener('keydown', function (event) {
            var panel = button.closest('[data-yperf-panel]');
            if (!panel) { return; }
            var list = Array.prototype.slice.call(panel.querySelectorAll('[data-yperf-subtab]'));
            var index = list.indexOf(button);
            if (index < 0) { return; }
            var next = index;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') { next = (index + 1) % list.length; }
            else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') { next = (index - 1 + list.length) % list.length; }
            else if (event.key === 'Home') { next = 0; }
            else if (event.key === 'End') { next = list.length - 1; }
            else { return; }
            event.preventDefault();
            list[next].click();
            list[next].focus();
        });
    });
    document.querySelectorAll('.yperf-tab-panel').forEach(function (panel) {
        var first = panel.querySelector('[data-yperf-subtab].is-active') || panel.querySelector('[data-yperf-subtab]');
        if (first) { activateSection(sectionKey(first), false); }
    });
    try {
        var requested = new URLSearchParams(window.location.search).get('yperf_section');
        if (requested) { activateSection(requested, false); }
    } catch (e) {}
}());

/* YPerf 1.81.0 — accessible progressive context help. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }


    var activeTrigger = null;
    var pinned = false;
    var popover = null;

    function ensurePopover() {
        if (popover) { return popover; }
        popover = document.createElement('div');
        popover.className = 'yperf-help-popover';
        popover.setAttribute('role', 'tooltip');
        popover.hidden = true;
        popover.innerHTML = '<strong></strong><p></p>';
        popover.addEventListener('mouseleave', function () { hide(false); });
        document.body.appendChild(popover);
        return popover;
    }

    function positionPopover(trigger) {
        var tip = ensurePopover();
        if (!trigger || tip.hidden) { return; }
        var rect = trigger.getBoundingClientRect();
        var margin = 10;
        var viewportPadding = 12;
        var width = Math.min(380, Math.max(240, window.innerWidth - (viewportPadding * 2)));
        tip.style.width = width + 'px';
        tip.style.left = '0px';
        tip.style.top = '0px';
        var popRect = tip.getBoundingClientRect();
        var left = rect.left + (rect.width / 2) - (popRect.width / 2);
        left = Math.max(viewportPadding, Math.min(left, window.innerWidth - popRect.width - viewportPadding));
        var top = rect.bottom + margin;
        if (top + popRect.height + viewportPadding > window.innerHeight && rect.top - popRect.height - margin >= viewportPadding) {
            top = rect.top - popRect.height - margin;
        }
        top = Math.max(viewportPadding, Math.min(top, window.innerHeight - popRect.height - viewportPadding));
        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(top) + 'px';
    }

    function show(trigger, pin) {
        if (!trigger) { return; }
        if (activeTrigger && activeTrigger !== trigger) {
            activeTrigger.setAttribute('aria-expanded', 'false');
            activeTrigger.removeAttribute('data-yperf-help-pinned');
        }
        activeTrigger = trigger;
        pinned = !!pin;
        var tip = ensurePopover();
        var title = trigger.getAttribute('data-yperf-help-title') || '';
        var body = trigger.getAttribute('data-yperf-help-body') || '';
        var titleNode = tip.querySelector('strong');
        var bodyNode = tip.querySelector('p');
        if (titleNode) { titleNode.textContent = title; }
        if (bodyNode) { bodyNode.textContent = body; }
        trigger.setAttribute('aria-expanded', 'true');
        if (pinned) { trigger.setAttribute('data-yperf-help-pinned', 'true'); }
        else { trigger.removeAttribute('data-yperf-help-pinned'); }
        tip.hidden = false;
        positionPopover(trigger);
    }

    function hide(force) {
        if (!activeTrigger) { return; }
        if (pinned && !force) { return; }
        activeTrigger.setAttribute('aria-expanded', 'false');
        activeTrigger.removeAttribute('data-yperf-help-pinned');
        activeTrigger = null;
        pinned = false;
        if (popover) { popover.hidden = true; }
    }

    document.addEventListener('mouseover', function (event) {
        var trigger = event.target.closest('[data-yperf-help-body]');
        if (trigger && !pinned) { show(trigger, false); }
    });

    document.addEventListener('mouseout', function (event) {
        var trigger = event.target.closest('[data-yperf-help-body]');
        if (!trigger || pinned) { return; }
        var related = event.relatedTarget;
        if (related && (trigger.contains(related) || (popover && popover.contains(related)))) { return; }
        hide(false);
    });

    document.addEventListener('focusin', function (event) {
        var trigger = event.target.closest('[data-yperf-help-body]');
        if (trigger && !pinned) { show(trigger, false); }
    });

    document.addEventListener('focusout', function (event) {
        var trigger = event.target.closest('[data-yperf-help-body]');
        if (!trigger || pinned) { return; }
        window.setTimeout(function () {
            if (document.activeElement !== trigger && (!popover || !popover.contains(document.activeElement))) {
                hide(false);
            }
        }, 0);
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-yperf-help-body]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            if (activeTrigger === trigger && pinned) {
                hide(true);
            } else {
                show(trigger, true);
            }
            return;
        }
        if (popover && popover.contains(event.target)) { return; }
        hide(true);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && activeTrigger) {
            var trigger = activeTrigger;
            hide(true);
            if (trigger && typeof trigger.focus === 'function') { trigger.focus(); }
        }
    });

    window.addEventListener('resize', function () {
        if (activeTrigger && popover && !popover.hidden) { positionPopover(activeTrigger); }
    });
    window.addEventListener('scroll', function () {
        if (activeTrigger && popover && !popover.hidden) { positionPopover(activeTrigger); }
    }, true);
}());

/* YPerf 1.85.2 — searchable Yablonsky AI text model picker with enforced filtered-row visibility. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    document.querySelectorAll('[data-yperf-model-picker]').forEach(function (root) {
        var toggle = root.querySelector('[data-yperf-model-toggle]');
        var popover = root.querySelector('[data-yperf-model-popover]');
        var search = root.querySelector('[data-yperf-model-search]');
        var value = root.querySelector('[data-yperf-model-value]');
        var current = root.querySelector('[data-yperf-model-current]');
        var options = Array.prototype.slice.call(root.querySelectorAll('.yperf-model-option'));
        var empty = root.querySelector('[data-yperf-model-empty]');
        var activeIndex = -1;
        if (!toggle || !popover || !search || !value || !current) { return; }

        function visibleOptions() { return options.filter(function (option) { return !option.hidden; }); }
        function positionPopover() {
            if (popover.hidden) { return; }
            var rect = toggle.getBoundingClientRect();
            var viewportWidth = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
            var viewportHeight = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
            var margin = 12;
            var gap = 6;
            var width = Math.min(Math.max(rect.width, 360), 620, Math.max(240, viewportWidth - (margin * 2)));
            var left = Math.min(Math.max(margin, rect.left), Math.max(margin, viewportWidth - width - margin));
            var spaceBelow = Math.max(0, viewportHeight - rect.bottom - gap - margin);
            var spaceAbove = Math.max(0, rect.top - gap - margin);
            var openAbove = spaceBelow < 260 && spaceAbove > spaceBelow;
            var available = Math.min(380, openAbove ? spaceAbove : spaceBelow);
            available = Math.max(120, available);

            popover.style.width = Math.round(width) + 'px';
            popover.style.left = Math.round(left) + 'px';
            popover.style.setProperty('--yperf-model-list-max', Math.max(76, Math.floor(available - 62)) + 'px');
            if (openAbove) {
                popover.style.top = 'auto';
                popover.style.bottom = Math.round(viewportHeight - rect.top + gap) + 'px';
            } else {
                popover.style.bottom = 'auto';
                popover.style.top = Math.round(rect.bottom + gap) + 'px';
            }
        }
        function close() {
            popover.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }
        function open() {
            search.value = '';
            filter();
            popover.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            positionPopover();
            search.focus();
            search.select();
        }
        function filter() {
            var query = (search.value || '').trim().toLowerCase();
            var visible = 0;
            options.forEach(function (option) {
                var haystack = ((option.getAttribute('data-model-label') || '') + ' ' + (option.getAttribute('data-model-id') || '')).toLowerCase();
                option.hidden = query !== '' && haystack.indexOf(query) === -1;
                if (!option.hidden) { visible += 1; }
            });
            if (empty) { empty.hidden = visible !== 0; }
            activeIndex = -1;
        }
        function choose(option) {
            var id = option.getAttribute('data-model-id') || 'auto';
            var label = option.getAttribute('data-model-label') || id;
            value.value = id;
            current.textContent = label;
            options.forEach(function (candidate) { candidate.setAttribute('aria-selected', candidate === option ? 'true' : 'false'); });
            close();
            toggle.focus();
        }
        function move(direction) {
            var visible = visibleOptions();
            if (!visible.length) { return; }
            var focusedIndex = visible.indexOf(document.activeElement);
            if (focusedIndex >= 0) {
                activeIndex = focusedIndex;
            }
            if (activeIndex < 0) {
                activeIndex = direction < 0 ? visible.length - 1 : 0;
            } else {
                activeIndex = Math.max(0, Math.min(visible.length - 1, activeIndex + direction));
            }
            visible[activeIndex].focus();
            visible[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        toggle.addEventListener('click', function () { popover.hidden ? open() : close(); });
        search.addEventListener('input', function () { filter(); positionPopover(); });
        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') { event.preventDefault(); move(1); }
            if (event.key === 'ArrowUp') { event.preventDefault(); move(-1); }
            if (event.key === 'Escape') { event.preventDefault(); close(); toggle.focus(); }
            if (event.key === 'Enter') {
                var visible = visibleOptions();
                if (visible.length === 1) { event.preventDefault(); choose(visible[0]); }
            }
        });
        options.forEach(function (option) {
            option.addEventListener('click', function () { choose(option); });
            option.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown') { event.preventDefault(); move(1); }
                if (event.key === 'ArrowUp') { event.preventDefault(); move(-1); }
                if (event.key === 'Escape') { event.preventDefault(); close(); toggle.focus(); }
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); choose(option); }
            });
        });
        document.addEventListener('click', function (event) { if (!root.contains(event.target)) { close(); } });
        window.addEventListener('resize', positionPopover);
        window.addEventListener('scroll', positionPopover, true);
    });
}());

/* YPerf 1.85.0 — truthful live migration activity console. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    var config = window.YPerfDashboard || {};
    var form = document.getElementById('yperf-migration-form');
    var button = document.getElementById('yperf-run-migration');
    var root = document.getElementById('yperf-migration-live');
    if (!form || !button || !root || !config.ajaxUrl || !config.migrationNonce) { return; }
    var bar = document.getElementById('yperf-migration-bar');
    var progress = document.getElementById('yperf-migration-progress');
    var percent = document.getElementById('yperf-migration-percent');
    var phase = document.getElementById('yperf-migration-phase');
    var detail = document.getElementById('yperf-migration-detail');
    var events = document.getElementById('yperf-migration-events');
    var stopped = false;

    function post(action) {
        var body = new URLSearchParams();
        body.append('action', action);
        body.append('_ajax_nonce', config.migrationNonce);
        return fetch(config.ajaxUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function (response) { return response.json(); });
    }
    function render(data) {
        var state = data && data.progress ? data.progress : data;
        if (!state || typeof state !== 'object') { return; }
        var n = Math.max(0, Math.min(100, parseInt(state.percent || 0, 10)));
        if (bar) { bar.style.width = n + '%'; }
        if (percent) { percent.textContent = n + '%'; }
        if (progress) { progress.setAttribute('aria-valuenow', String(n)); }
        if (phase && state.label) { phase.textContent = state.label; }
        if (detail && state.detail) { detail.textContent = state.detail; }
        root.querySelectorAll('[data-threshold]').forEach(function (stage) {
            var done = n >= parseInt(stage.getAttribute('data-threshold') || '101', 10);
            stage.classList.toggle('is-complete', done);
            var marker = stage.querySelector('span');
            if (marker) { marker.textContent = done ? '✓' : '○'; }
        });
        if (events && Array.isArray(state.events)) {
            events.innerHTML = '';
            state.events.forEach(function (entry) {
                var row = document.createElement('div');
                var time = document.createElement('time');
                var message = document.createElement('span');
                var stamp = entry && entry.time ? new Date(parseInt(entry.time, 10) * 1000) : new Date();
                time.textContent = stamp.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                message.textContent = entry && entry.message ? entry.message : '';
                row.appendChild(time); row.appendChild(message); events.appendChild(row);
            });
            events.scrollTop = events.scrollHeight;
        }
    }
    function poll() {
        if (stopped) { return; }
        post('yperf_migration_status').then(function (json) {
            if (json && json.success) { render(json.data); }
        }).catch(function () {}).finally(function () {
            if (!stopped) { window.setTimeout(poll, 700); }
        });
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (button.disabled) { return; }
        button.disabled = true;
        stopped = false;
        if (phase) { phase.textContent = (config.strings && config.strings.migrationStarting) || 'Preparing protected handoff…'; }
        poll();
        post('yperf_migration_run').then(function (json) {
            if (json && json.data && json.data.progress) { render(json.data); }
            if (!json || !json.success) {
                var message = json && json.data && json.data.message ? json.data.message : ((config.strings && config.strings.migrationStopped) || 'Migration stopped safely.');
                throw new Error(message);
            }
            var result = json.data && json.data.result ? json.data.result : {};
            if (result.requires_user_action || result.state === 'user_action_required') {
                stopped = true;
                button.disabled = false;
                if (phase) { phase.textContent = yperfI18n('migrationConfirmRequired', 'Your confirmation is required'); }
                if (detail) { detail.textContent = result.message || yperfI18n('migrationManualDeactivate', 'Deactivate the previous performance plugin from the normal WordPress Plugins screen, then continue the migration here.'); }
                window.setTimeout(function () { window.location.reload(); }, 650);
                return;
            }
            stopped = true;
            if (phase) { phase.textContent = (config.strings && config.strings.migrationCompleted) || 'Migration complete. YPerf ownership is proven.'; }
            window.setTimeout(function () { window.location.reload(); }, 900);
        }).catch(function (error) {
            stopped = true;
            button.disabled = false;
            if (phase) { phase.textContent = (config.strings && config.strings.migrationStopped) || 'Migration stopped safely.'; }
            if (detail) { detail.textContent = error && error.message ? error.message : yperfI18n('migrationReviewEvent', 'Review the latest migration event.'); }
        });
    });
}());

/* YPerf 1.86.1 — explicit Runtime Acceptance naming, dual readiness metrics, and observable evidence-refresh completion. */

/* YPerf 1.92.0 — guided migration operations stay visible, explicit and reversible in the active step. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    var config = window.YPerfDashboard || {};
    if (!config.ajaxUrl || !config.migrationNonce) { return; }

    function ajax(action, extra) {
        var body = new URLSearchParams();
        body.append('action', action);
        body.append('_ajax_nonce', config.migrationNonce);
        Object.keys(extra || {}).forEach(function (key) { body.append(key, extra[key]); });
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function (response) { return response.json(); });
    }

    function setCompanion(percent, status) {
        var n = Math.max(0, Math.min(100, parseInt(percent || 0, 10)));
        document.querySelectorAll('[data-yperf-companion-bar]').forEach(function (bar) { bar.style.width = n + '%'; });
        document.querySelectorAll('[data-yperf-companion-percent]').forEach(function (node) { node.textContent = n + '%'; });
        document.querySelectorAll('[data-yperf-companion-status]').forEach(function (node) { node.textContent = status || ''; });
    }

    function renderEvents(root, events) {
        if (!root || !Array.isArray(events)) { return; }
        root.innerHTML = '';
        events.forEach(function (entry) {
            var row = document.createElement('div');
            var time = document.createElement('time');
            var message = document.createElement('span');
            var stamp = entry && entry.time ? new Date(parseInt(entry.time, 10) * 1000) : new Date();
            time.textContent = stamp.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            message.textContent = entry && entry.message ? entry.message : '';
            row.appendChild(time);
            row.appendChild(message);
            root.appendChild(row);
        });
        root.scrollTop = root.scrollHeight;
    }

    function renderBaseline(state) {
        if (!state || typeof state !== 'object') { return; }
        var wrap = document.querySelector('[data-yperf-baseline-progress]');
        if (!wrap) { return; }
        wrap.hidden = false;
        var n = Math.max(0, Math.min(100, parseInt(state.percent || 0, 10)));
        var bar = wrap.querySelector('[data-yperf-baseline-bar]');
        var pct = wrap.querySelector('[data-yperf-baseline-percent]');
        var label = wrap.querySelector('[data-yperf-baseline-label]');
        var phase = wrap.querySelector('[data-yperf-baseline-phase]');
        var detail = wrap.querySelector('[data-yperf-baseline-detail]');
        if (bar) { bar.style.width = n + '%'; }
        if (pct) { pct.textContent = n + '%'; }
        if (label) { label.textContent = state.label || ''; }
        if (phase) { phase.textContent = state.label || ''; }
        if (detail) { detail.textContent = state.detail || ''; }
        var root = bar ? bar.parentElement : null;
        if (root) { root.setAttribute('aria-valuenow', String(n)); }
        wrap.querySelectorAll('[data-threshold]').forEach(function (stage) {
            var threshold = parseInt(stage.getAttribute('data-threshold') || '101', 10);
            var done = n >= threshold;
            stage.classList.toggle('is-complete', done);
            stage.classList.toggle('is-active', !done && n > 0 && n < threshold && (threshold - n) <= 25);
            var marker = stage.querySelector('span');
            if (marker) { marker.textContent = done ? '✓' : (stage.classList.contains('is-active') ? '•' : '○'); }
        });
        renderEvents(wrap.querySelector('[data-yperf-baseline-events]'), state.events || []);
        var terminal = !!state.terminal || n >= 100;
        wrap.classList.toggle('is-running', !terminal);
        wrap.classList.toggle('is-complete', state.phase === 'accepted');
        wrap.classList.toggle('is-error', ['error', 'blocked', 'rolled_back'].indexOf(state.phase) >= 0);
        setCompanion(n, state.label || state.detail || '');
    }

    var baselinePolling = false;
    function pollBaseline() {
        if (!baselinePolling) { return; }
        ajax('yperf_migration_baseline_status').then(function (json) {
            if (json && json.success && json.data) { renderBaseline(json.data.progress); }
        }).catch(function () {}).finally(function () {
            if (baselinePolling) { window.setTimeout(pollBaseline, 550); }
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target && event.target.matches('[data-yperf-migration-baseline-form]') ? event.target : null;
        if (!form) { return; }
        event.preventDefault();
        var button = form.querySelector('[data-yperf-baseline-button]');
        var profile = form.getAttribute('data-yperf-profile') || 'safe';
        if (button) { button.disabled = true; }
        var wrap = document.querySelector('[data-yperf-baseline-progress]');
        if (wrap) { wrap.hidden = false; wrap.classList.add('is-running'); wrap.classList.remove('is-complete', 'is-error'); }
        setCompanion(0, (config.strings && config.strings.baselineStarting) || 'Preparing safe YPerf baseline…');
        baselinePolling = true;
        pollBaseline();
        ajax('yperf_migration_baseline_run', {profile: profile}).then(function (json) {
            if (json && json.data && json.data.progress) { renderBaseline(json.data.progress); }
            baselinePolling = false;
            if (!json || !json.success) {
                var message = json && json.data && json.data.result && json.data.result.message ? json.data.result.message : ((config.strings && config.strings.baselineStopped) || 'Baseline action stopped safely.');
                throw new Error(message);
            }
            setCompanion(100, (config.strings && config.strings.baselineCompleted) || 'Baseline accepted. Runtime proof is clean.');
            window.setTimeout(function () {
                if (window.YPerfRefreshMigrationFragments) {
                    window.YPerfRefreshMigrationFragments();
                }
            }, 900);
        }).catch(function (error) {
            baselinePolling = false;
            if (button) { button.disabled = false; }
            if (wrap) { wrap.classList.remove('is-running'); wrap.classList.add('is-error'); }
            setCompanion(100, error && error.message ? error.message : 'Baseline action stopped safely.');
        });
    });

    function responsibilityProgress(percent, label, doneCount, state) {
        var wrap = document.querySelector('[data-yperf-responsibility-progress]');
        if (!wrap) { return; }
        wrap.hidden = false;
        var n = Math.max(0, Math.min(100, parseInt(percent || 0, 10)));
        var bar = wrap.querySelector('[data-yperf-responsibility-bar]');
        var pct = wrap.querySelector('[data-yperf-responsibility-percent]');
        var text = wrap.querySelector('[data-yperf-responsibility-label]');
        if (bar) { bar.style.width = n + '%'; }
        if (pct) { pct.textContent = n + '%'; }
        if (text) { text.textContent = label || ''; }
        var stages = wrap.querySelectorAll('.yperf-migration-mini-stages > div');
        stages.forEach(function (stage, index) {
            var complete = index < (doneCount || 0);
            stage.classList.toggle('is-complete', complete);
            stage.classList.toggle('is-active', index === (doneCount || 0) && n < 100);
            var marker = stage.querySelector('span');
            if (marker) { marker.textContent = complete ? '✓' : (stage.classList.contains('is-active') ? '•' : '○'); }
        });
        wrap.classList.toggle('is-running', n > 0 && n < 100 && state !== 'error');
        wrap.classList.toggle('is-complete', n >= 100 && state !== 'error');
        wrap.classList.toggle('is-error', state === 'error');
        setCompanion(n, label || '');
    }

    function setResponsibilityControlsDisabled(disabled) {
        document.querySelectorAll('[data-yperf-retire-responsibility], [data-yperf-recheck-responsibilities]').forEach(function (button) {
            button.disabled = !!disabled;
        });
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-yperf-toggle-migration-technical]');
        if (toggle) {
            var technical = document.getElementById('yperf-migration-technical');
            if (technical) { technical.open = !technical.open; }
            return;
        }

        var retire = event.target.closest('[data-yperf-retire-responsibility]');
        if (retire) {
            var responsibility = retire.getAttribute('data-yperf-retire-responsibility') || '';
            if (!responsibility) { return; }
            setResponsibilityControlsDisabled(true);
            responsibilityProgress(8, (config.strings && config.strings.responsibilityRetireStarting) || 'Preparing explicit provider retirement…', 0);
            window.setTimeout(function () {
                responsibilityProgress(28, (config.strings && config.strings.responsibilityRetireSnapshot) || 'Capturing the reversible W3TC state…', 1);
            }, 120);
            window.setTimeout(function () {
                responsibilityProgress(52, (config.strings && config.strings.responsibilityRetireApply) || 'Applying the selected W3TC retirement…', 2);
            }, 260);
            ajax('yperf_migration_retire_responsibility', {responsibility: responsibility}).then(function (json) {
                if (!json || !json.success) {
                    var message = json && json.data && json.data.message ? json.data.message : ((config.strings && config.strings.responsibilityRetireStopped) || 'Provider retirement stopped safely.');
                    throw new Error(message);
                }
                responsibilityProgress(78, (config.strings && config.strings.responsibilityRetireVerify) || 'Reading the provider state back…', 3);
                var message = json.data && json.data.result && json.data.result.message
                    ? json.data.result.message
                    : ((config.strings && config.strings.responsibilityRetireComplete) || 'Provider responsibility retired and verified.');
                window.setTimeout(function () {
                    responsibilityProgress(100, message, 4);
                    window.setTimeout(function () {
                        if (window.YPerfRefreshMigrationFragments) { window.YPerfRefreshMigrationFragments(); }
                    }, 650);
                }, 240);
            }).catch(function (error) {
                setResponsibilityControlsDisabled(false);
                responsibilityProgress(100, error && error.message ? error.message : ((config.strings && config.strings.responsibilityRetireStopped) || 'Provider retirement stopped safely.'), 2, 'error');
            });
            return;
        }

        var recheck = event.target.closest('[data-yperf-recheck-responsibilities]');
        if (!recheck) { return; }
        setResponsibilityControlsDisabled(true);
        responsibilityProgress(12, (config.strings && config.strings.responsibilityChecking) || 'Rechecking provider responsibilities…', 0);
        window.setTimeout(function () { responsibilityProgress(34, 'Reading W3 Total Cache provider state…', 1); }, 120);
        window.setTimeout(function () { responsibilityProgress(64, 'Inspecting ownership and drop-ins…', 2); }, 260);
        ajax('yperf_migration_responsibility_check').then(function (json) {
            if (!json || !json.success) { throw new Error(yperfI18n('responsibilityCheckFailed', 'Provider responsibility check failed.')); }
            var ready = !!(json.data && json.data.ready);
            responsibilityProgress(100, ready
                ? ((config.strings && config.strings.responsibilityReady) || 'Provider responsibilities are ready for protected handover.')
                : ((config.strings && config.strings.responsibilityBlocked) || 'Provider responsibilities still need attention.'), 4);
            window.setTimeout(function () {
                if (window.YPerfRefreshMigrationFragments) { window.YPerfRefreshMigrationFragments(); }
            }, 700);
        }).catch(function (error) {
            setResponsibilityControlsDisabled(false);
            responsibilityProgress(100, error && error.message ? error.message : yperfI18n('responsibilityCheckStopped', 'Responsibility check stopped.'), 2, 'error');
        });
    });

    function renderGuidedHandover(state) {
        if (!state || typeof state !== 'object') { return; }
        var root = document.getElementById('yperf-guided-migration-live');
        if (!root) { return; }
        var n = Math.max(0, Math.min(100, parseInt(state.percent || 0, 10)));
        var bar = document.getElementById('yperf-guided-migration-bar');
        var progress = document.getElementById('yperf-guided-migration-progress');
        var pct = document.getElementById('yperf-guided-migration-percent');
        var phase = document.getElementById('yperf-guided-migration-phase');
        var detail = document.getElementById('yperf-guided-migration-detail');
        if (bar) { bar.style.width = n + '%'; }
        if (pct) { pct.textContent = n + '%'; }
        if (progress) { progress.setAttribute('aria-valuenow', String(n)); }
        if (phase && state.label) { phase.textContent = state.label; }
        if (detail && state.detail) { detail.textContent = state.detail; }
        root.querySelectorAll('[data-threshold]').forEach(function (stage) {
            var done = n >= parseInt(stage.getAttribute('data-threshold') || '101', 10);
            stage.classList.toggle('is-complete', done);
            var marker = stage.querySelector('span');
            if (marker) { marker.textContent = done ? '✓' : '○'; }
        });
        renderEvents(document.getElementById('yperf-guided-migration-events'), state.events || []);
        var terminal = !!state.terminal || n >= 100;
        root.classList.toggle('is-running', !terminal && n > 0);
        root.classList.toggle('is-complete', terminal && state.phase === 'complete');
        root.classList.toggle('is-error', terminal && state.phase !== 'complete');
        setCompanion(n, state.label || state.detail || '');
    }

    var handoverPolling = false;
    function pollHandover() {
        if (!handoverPolling) { return; }
        ajax('yperf_migration_status').then(function (json) {
            if (json && json.success && json.data) { renderGuidedHandover(json.data.progress); }
        }).catch(function () {}).finally(function () {
            if (handoverPolling) { window.setTimeout(pollHandover, 650); }
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target && event.target.id === 'yperf-guided-migration-form' ? event.target : null;
        if (!form) { return; }
        event.preventDefault();
        var button = document.getElementById('yperf-guided-run-migration');
        if (!button || button.disabled) { return; }
        button.disabled = true;
        handoverPolling = true;
        setCompanion(0, (config.strings && config.strings.migrationStarting) || 'Preparing protected handoff…');
        pollHandover();
        ajax('yperf_migration_run').then(function (json) {
            if (json && json.data && json.data.progress) { renderGuidedHandover(json.data.progress); }
            handoverPolling = false;
            if (!json || !json.success) {
                var message = json && json.data && json.data.message ? json.data.message : ((config.strings && config.strings.migrationStopped) || 'Migration stopped safely.');
                throw new Error(message);
            }
            var result = json.data && json.data.result ? json.data.result : {};
            if (result.requires_user_action || result.state === 'user_action_required') {
                button.disabled = false;
                setCompanion((json.data.progress && json.data.progress.percent) || 25, result.message || yperfI18n('migrationManualDeactivate', 'Deactivate the previous performance plugin from the normal WordPress Plugins screen, then continue the migration here.'));
                window.setTimeout(function () {
                    if (window.YPerfRefreshMigrationFragments) { window.YPerfRefreshMigrationFragments(); }
                    else { window.location.reload(); }
                }, 650);
                return;
            }
            setCompanion(100, (config.strings && config.strings.migrationCompleted) || 'Migration complete. YPerf ownership is proven.');
            window.setTimeout(function () {
                if (window.YPerfRefreshMigrationFragments) { window.YPerfRefreshMigrationFragments(); }
            }, 900);
        }).catch(function (error) {
            handoverPolling = false;
            button.disabled = false;
            setCompanion(100, error && error.message ? error.message : 'Migration stopped safely.');
        });
    });
}());

/* YPerf 1.92.2 — post-handover drop-in reconciliation and local-core production gate clarity. */

/* YPerf 1.94.0 — guided public-delivery stability with server-declared bounded steps. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    var config = window.YPerfDashboard || {};
    var form = document.querySelector('[data-yperf-page-cache-campaign-form]');
    var button = document.querySelector('[data-yperf-page-cache-campaign-run]');
    var root = document.querySelector('[data-yperf-page-cache-campaign-progress]');
    if (!form || !button || !root || !config.ajaxUrl || !config.pageCacheCampaignNonce) { return; }

    var bar = root.querySelector('[data-yperf-page-cache-campaign-bar]');
    var percent = root.querySelector('[data-yperf-page-cache-campaign-percent]');
    var label = root.querySelector('[data-yperf-page-cache-campaign-label]');
    var events = root.querySelector('[data-yperf-page-cache-campaign-events]');
    var phases = Array.prototype.slice.call(root.querySelectorAll('[data-yperf-page-cache-campaign-phase]'));

    function request(action, extra) {
        var body = new URLSearchParams();
        body.append('action', action);
        body.append('_ajax_nonce', config.pageCacheCampaignNonce);
        Object.keys(extra || {}).forEach(function (key) { body.append(key, extra[key]); });
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok || !json || !json.success) {
                    var message = json && json.data && json.data.message ? json.data.message : ((config.strings && config.strings.pageCacheCampaignStopped) || 'Page Cache stability campaign stopped safely.');
                    var error = new Error(message);
                    error.yperfState = json && json.data && json.data.state ? json.data.state : null;
                    throw error;
                }
                return json.data || {};
            });
        });
    }

    function phaseIndex(state) {
        var phase = state && state.phase ? String(state.phase) : '';
        var progress = state ? parseInt(state.progress || 0, 10) : 0;
        if (phase === 'complete') { return 4; }
        if (phase === 'final_acceptance') { return 4; }
        if (phase === 'stability') { return 3; }
        if (['activation', 'cache_proof', 'post_activation_acceptance', 'accepted'].indexOf(phase) !== -1) { return 2; }
        if (phase === 'error') {
            if (progress >= 92) { return 4; }
            if (progress >= 58) { return 3; }
            if (progress >= 32) { return 2; }
        }
        return 1;
    }

    function renderEvents(items) {
        if (!events) { return; }
        events.innerHTML = '';
        (Array.isArray(items) ? items.slice(-6) : []).forEach(function (entry) {
            var row = document.createElement('div');
            var time = document.createElement('span');
            var message = document.createElement('strong');
            var date = entry && entry.time ? new Date(parseInt(entry.time, 10) * 1000) : null;
            time.textContent = date && !isNaN(date.getTime()) ? date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : '';
            message.textContent = entry && entry.message ? entry.message : '';
            row.appendChild(time);
            row.appendChild(message);
            events.appendChild(row);
        });
    }

    function renderState(state) {
        state = state || {};
        var value = Math.max(0, Math.min(100, parseInt(state.progress || 0, 10)));
        root.hidden = false;
        root.classList.toggle('is-running', state.status === 'running');
        root.classList.toggle('is-complete', state.status === 'complete');
        root.classList.toggle('is-error', state.status === 'error');
        if (bar) { bar.style.width = value + '%'; }
        if (percent) { percent.textContent = value + '%'; }
        if (label) { label.textContent = state.message || ''; }
        var progress = bar ? bar.parentElement : null;
        if (progress) { progress.setAttribute('aria-valuenow', String(value)); }

        var current = phaseIndex(state);
        phases.forEach(function (node, index) {
            var number = index + 1;
            node.classList.remove('is-active', 'is-complete', 'is-error');
            var marker = node.querySelector('span');
            if (state.status === 'error' && number === current) {
                node.classList.add('is-error');
                if (marker) { marker.textContent = '!'; }
            } else if (state.status === 'complete' || number < current) {
                node.classList.add('is-complete');
                if (marker) { marker.textContent = '✓'; }
            } else if (number === current && state.status === 'running') {
                node.classList.add('is-active');
                if (marker) { marker.textContent = '•'; }
            } else if (marker) {
                marker.textContent = '○';
            }
        });
        renderEvents(state.events || []);
    }

    function nextPaint() {
        return new Promise(function (resolve) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(resolve);
            });
        });
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (button.disabled) { return; }

        var idleButtonText = button.textContent;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = (config.strings && config.strings.pageCacheCampaignStartingShort) || yperfI18n('pageCacheStartingShort', 'Starting…');

        // Paint a truthful local acknowledgement before WordPress/PHP performs
        // the first server-side campaign request. On slower wp-admin stacks the
        // AJAX round-trip can take several seconds; without this paint boundary
        // the click looks dead even though the campaign is starting normally.
        renderState({
            status: 'running',
            phase: 'preflight',
            progress: 1,
            message: (config.strings && config.strings.pageCacheCampaignStarting) || yperfI18n('pageCacheStarting', 'Starting public delivery stability campaign…'),
            events: [{time: Math.floor(Date.now() / 1000), message: yperfI18n('pageCachePreflightAccepted', 'Request accepted. Preparing fresh preflight…')}]
        });
        root.scrollIntoView({block: 'nearest', behavior: 'smooth'});
        await nextPaint();

        try {
            var started = await request('yperf_page_cache_campaign_start');
            var state = started.state || {};
            renderState(state);
            var safetySteps = 0;
            while (state && state.status === 'running' && state.expected_step && state.expected_step !== 'complete') {
                safetySteps += 1;
                if (safetySteps > 20) {
                    throw new Error((config.strings && config.strings.pageCacheCampaignBoundedStop) || 'Page Cache campaign exceeded its bounded safety step limit.');
                }
                var data = await request('yperf_page_cache_campaign_step', {step: state.expected_step});
                state = data.state || {};
                renderState(state);
            }
            if (label) { label.textContent = (config.strings && config.strings.pageCacheCampaignComplete) || 'Public delivery stability campaign complete.'; }
            button.removeAttribute('aria-busy');
            window.setTimeout(function () { window.location.reload(); }, 1200);
        } catch (error) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.textContent = idleButtonText;
            if (error && error.yperfState) { renderState(error.yperfState); }
            root.classList.remove('is-running');
            root.classList.add('is-error');
            if (label) { label.textContent = error && error.message ? error.message : ((config.strings && config.strings.pageCacheCampaignStopped) || 'Page Cache stability campaign stopped safely.'); }
        }
    });

    // YPerf 2.10.0 — Optimize Now uses the real guarded campaign ledger.
    // The server remains authoritative for phase and percentage; the browser
    // only renders state that executePerformanceCampaign() has actually reached.
    (function initAutopilotProgress() {
        var root = document.querySelector('[data-yperf-autopilot-progress]');
        var forms = Array.prototype.slice.call(document.querySelectorAll('.yperf-header-optimize-form, .yperf-autopilot-action')).filter(function (item) {
            return item && item.tagName === 'FORM';
        });
        if (!root || !forms.length || !config.ajaxUrl || !config.autopilotNonce) { return; }

        var bar = root.querySelector('[data-yperf-autopilot-bar]');
        var percent = root.querySelector('[data-yperf-autopilot-percent]');
        var label = root.querySelector('[data-yperf-autopilot-label]');
        var phase = root.querySelector('[data-yperf-autopilot-phase]');
        var detail = root.querySelector('[data-yperf-autopilot-detail]');
        var eventsRoot = root.querySelector('[data-yperf-autopilot-events]');
        var progressNode = bar ? bar.parentElement : null;
        var running = false;
        var pollTimer = null;
        var originalButtonText = new Map();
        var lastProgress = null;
        var prewarmActive = false;
        var commandStartedAt = 0;
        var recoveringTransport = false;

        function request(action, extra) {
            var body = new URLSearchParams();
            body.append('action', action);
            body.append('_ajax_nonce', config.autopilotNonce);
            if (extra && typeof extra === 'object') {
                Object.keys(extra).forEach(function (key) { body.append(key, String(extra[key])); });
            }
            return fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            }).then(function (response) {
                return response.json().catch(function () { return {success: false, data: {}}; });
            });
        }

        function renderEvents(events) {
            if (!eventsRoot || !Array.isArray(events)) { return; }
            eventsRoot.innerHTML = '';
            events.forEach(function (entry) {
                var row = document.createElement('div');
                var time = document.createElement('time');
                var message = document.createElement('span');
                var stamp = entry && entry.time ? new Date(parseInt(entry.time, 10) * 1000) : new Date();
                time.textContent = stamp.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                message.textContent = entry && entry.message ? entry.message : '';
                row.appendChild(time);
                row.appendChild(message);
                eventsRoot.appendChild(row);
            });
            eventsRoot.scrollTop = eventsRoot.scrollHeight;
        }

        function render(state) {
            if (!state || typeof state !== 'object') { return; }
            lastProgress = state;
            root.hidden = false;
            var terminal = !!state.terminal;
            var rawPercent = Math.max(0, Math.min(100, parseInt(state.percent || 0, 10)));
            // YPerf 2.34.4 — 100% is a terminal truth, not an estimate. If a
            // future/background phase is still running, keep the visual below
            // 100 so the administrator never sees "done" while work remains.
            var n = (!terminal && rawPercent >= 100) ? 99 : rawPercent;
            if (bar) { bar.style.width = n + '%'; }
            if (percent) { percent.textContent = n + '%'; }
            if (label) { label.textContent = state.label || ((config.strings && config.strings.baselineStarting) || 'Preparing safe YPerf baseline…'); }
            if (phase) { phase.textContent = state.label || ''; }
            if (detail) { detail.textContent = state.detail || ''; }
            if (progressNode) { progressNode.setAttribute('aria-valuenow', String(n)); }
            var stages = Array.prototype.slice.call(root.querySelectorAll('[data-threshold]'));
            var errorPhase = ['error', 'blocked', 'rolled_back'].indexOf(state.phase) >= 0;
            var failureIndex = -1;
            if (errorPhase && stages.length) {
                stages.forEach(function (stage, index) {
                    var threshold = parseInt(stage.getAttribute('data-threshold') || '101', 10);
                    if (n >= threshold) { failureIndex = index; }
                });
                if (failureIndex < 0 && n > 0) { failureIndex = 0; }
            }
            function phaseStepIndex(phaseName) {
                switch (phaseName) {
                    case 'starting':
                    case 'preflight':
                    case 'preflight_ready':
                        return 0;
                    case 'apply':
                        return 1;
                    case 'runtime_proof':
                        return 2;
                    case 'regression_confirmation':
                    case 'regression_guard':
                        return 3;
                    case 'finalizing':
                    case 'prewarm':
                    case 'prewarm_ready':
                    case 'accepted':
                    case 'rollback':
                    case 'rolled_back':
                    case 'blocked':
                    case 'error':
                        return 4;
                    default:
                        return n <= 0 ? -1 : (n >= 100 ? 4 : Math.max(0, Math.min(stages.length - 1, Math.floor(n / 25))));
                }
            }
            var activeIndex = phaseStepIndex(String(state.phase || ''));
            stages.forEach(function (stage, index) {
                var done = false;
                var failed = false;
                var active = false;
                if (errorPhase) {
                    done = index < failureIndex;
                    failed = index === failureIndex;
                } else if (terminal && state.phase === 'accepted') {
                    done = true;
                } else {
                    done = activeIndex >= 0 && index < activeIndex;
                    active = activeIndex >= 0 && index === activeIndex;
                }
                stage.classList.toggle('is-complete', done);
                stage.classList.toggle('is-active', active);
                stage.classList.toggle('is-error', failed);
                var marker = stage.querySelector('span');
                if (marker) { marker.textContent = done ? '✓' : (failed ? '!' : (active ? '◉' : '○')); }
            });
            renderEvents(state.events || []);
            root.classList.toggle('is-running', !terminal);
            root.classList.toggle('is-complete', state.phase === 'accepted');
            root.classList.toggle('is-error', ['error', 'blocked', 'rolled_back'].indexOf(state.phase) >= 0);
        }

        function setButtonsBusy(busy) {
            forms.forEach(function (form) {
                var button = form.querySelector('button[type="submit"]');
                if (!button) { return; }
                if (!originalButtonText.has(button)) { originalButtonText.set(button, button.textContent); }
                button.disabled = busy;
                if (busy) {
                    button.setAttribute('aria-busy', 'true');
                    button.textContent = (config.strings && config.strings.baselineStarting) || 'Preparing safe YPerf baseline…';
                } else {
                    button.removeAttribute('aria-busy');
                    button.textContent = originalButtonText.get(button) || yperfI18n('optimizeNow', 'Optimize Now');
                }
            });
        }

        function stopPolling() {
            if (pollTimer) { window.clearTimeout(pollTimer); pollTimer = null; }
        }

        function prewarmProgress(done, total) {
            var ratio = total > 0 ? Math.max(0, Math.min(1, done / total)) : 1;
            var n = Math.min(99, 94 + Math.round(ratio * 5));
            var base = lastProgress && typeof lastProgress === 'object' ? lastProgress : {};
            render({
                phase: 'prewarm',
                percent: n,
                label: base.label || yperfI18n('prewarmAcceptedCache', 'Prewarming the accepted public cache.'),
                detail: total > 0 ? ('Warming ' + Math.min(done + 1, total) + '/' + total) : (base.detail || ''),
                terminal: false,
                updated_at: Math.floor(Date.now() / 1000),
                events: Array.isArray(base.events) ? base.events : []
            });
        }

        function finalizeAcceptedPrewarm(prewarmOk) {
            return request('yperf_autopilot_prewarm_finalize', {prewarm_ok: prewarmOk === false ? '0' : '1'}).then(function (json) {
                if (!json || !json.success) { throw new Error(yperfI18n('prewarmFinalizationFailed', 'Automatic prewarm finalization failed.')); }
                if (json.data && json.data.progress) { render(json.data.progress); }
                return json;
            });
        }

        function runAcceptedPrewarm() {
            prewarmActive = true;
            if (typeof window.yperfRunBoundedPrewarm !== 'function') {
                return finalizeAcceptedPrewarm(false);
            }
            return window.yperfRunBoundedPrewarm({limit: 6, silent: true, onProgress: prewarmProgress})
                .then(function () { return finalizeAcceptedPrewarm(true); })
                .catch(function () {
                    // The performance baseline is already accepted before this
                    // optional warm-up phase starts. A transport/prewarm problem
                    // must never leave Optimize Now frozen at 94% or mislabel the
                    // accepted baseline as rolled back. Finalize the accepted state
                    // and let the separate Prewarm tool be retried later if needed.
                    return finalizeAcceptedPrewarm(false);
                });
        }

        function poll() {
            if (!running || prewarmActive) { return; }
            request('yperf_autopilot_status').then(function (json) {
                if (json && json.success && json.data && json.data.progress) {
                    render(json.data.progress);
                    if (json.data.progress.terminal) { running = false; }
                }
            }).catch(function () {}).finally(function () {
                if (running && !prewarmActive) { pollTimer = window.setTimeout(poll, 550); }
            });
        }

        // A lost admin-ajax response does not mean the server-side campaign
        // failed. The existing authenticated ledger is authoritative. Recovery
        // reads status only; it NEVER starts a second optimization campaign.
        function recoverAfterTransportLoss() {
            recoveringTransport = true;
            running = true;
            stopPolling();
            setButtonsBusy(true);
            root.classList.remove('is-error');
            root.classList.add('is-running');
            if (label) {
                label.textContent = (config.strings && config.strings.autopilotReconnecting) ||
                    'Connection interrupted. Checking the server result…';
            }
            if (detail) {
                detail.textContent = (config.strings && config.strings.autopilotNoRetry) ||
                    'The original operation may still be running. Do not start it again.';
            }
            var attempts = 0;
            var maxAttempts = 36;
            function reconcile() {
                attempts += 1;
                return request('yperf_autopilot_status').then(function (json) {
                    var state = json && json.success && json.data ? json.data.progress : null;
                    if (!state || typeof state !== 'object' || !state.updated_at) { return 'retry'; }
                    // Reject the previous campaign's ledger if the current
                    // server request has not yet updated its own state.
                    var timestamp = parseInt(state.updated_at, 10) || 0;
                    if (commandStartedAt && timestamp < commandStartedAt - 10) { return 'retry'; }
                    render(state);
                    if (state.phase === 'prewarm' || state.phase === 'prewarm_ready') {
                        recoveringTransport = false;
                        stopPolling();
                        return runAcceptedPrewarm().then(function () { return 'accepted'; }, function () { return 'retry'; });
                    }
                    if (state.terminal) {
                        return state.phase === 'accepted' ? 'accepted' : 'terminal';
                    }
                    return 'retry';
                }, function () { return 'retry'; }).then(function (result) {
                    if (result === 'accepted') {
                        recoveringTransport = false;
                        running = false;
                        stopPolling();
                        window.setTimeout(function () { window.location.reload(); }, 900);
                        return;
                    }
                    if (result === 'terminal') {
                        recoveringTransport = false;
                        running = false;
                        stopPolling();
                        setButtonsBusy(false);
                        return;
                    }
                    if (attempts >= maxAttempts) {
                        // Inconclusive, not failed. Do not silently enable a
                        // second Optimize Now run while the first may be live.
                        recoveringTransport = false;
                        running = false;
                        root.classList.remove('is-running', 'is-error');
                        if (label) {
                            label.textContent = (config.strings && config.strings.autopilotUnverified) ||
                                'Server completion not verified. Reload this page before any retry.';
                        }
                        if (detail) {
                            detail.textContent = (config.strings && config.strings.autopilotNoRetry) ||
                                'The original operation may still be running. Do not start it again.';
                        }
                        // Buttons deliberately remain disabled until reload.
                        return;
                    }
                    return new Promise(function (resolve) {
                        window.setTimeout(resolve, 2500);
                    }).then(reconcile);
                });
            }
            return reconcile();
        }

        forms.forEach(function (optimizeForm) {
            optimizeForm.addEventListener('submit', function (event) {
                if (running) { event.preventDefault(); return; }
                event.preventDefault();
                running = true;
                commandStartedAt = Math.floor(Date.now() / 1000);
                recoveringTransport = false;
                stopPolling();
                setButtonsBusy(true);
                root.hidden = false;
                root.classList.add('is-running');
                root.classList.remove('is-complete', 'is-error');
                render({
                    phase: 'starting',
                    percent: 1,
                    label: (config.strings && config.strings.baselineStarting) || 'Preparing safe YPerf baseline…',
                    detail: (config.strings && config.strings.starting) || 'Preparing checks…',
                    terminal: false,
                    events: []
                });
                root.scrollIntoView({block: 'nearest', behavior: 'smooth'});

                // Start the authoritative campaign request first so the status
                // poll cannot briefly resurrect a terminal state from the prior
                // campaign before the new ledger has been reset.
                var runRequest = request('yperf_autopilot_run');
                pollTimer = window.setTimeout(poll, 300);

                runRequest.then(function (json) {
                    var data = json && json.data ? json.data : {};
                    if (data.progress) { render(data.progress); }
                    if (!json || !json.success) {
                        var message = data.result && data.result.message ? data.result.message : ((config.strings && config.strings.baselineStopped) || 'Baseline action stopped safely.');
                        throw new Error(message);
                    }

                    // YPerf 2.34.5 — acceptance and automatic prewarm are two
                    // bounded requests. The expensive proof campaign returns after
                    // accepted evidence is committed at 94%; the existing per-URL
                    // prewarm runner then advances 94 -> 99 in real work steps and
                    // a tiny finalizer publishes terminal 100. This avoids spending
                    // the exhausted proof-request execution budget on loopback HTTP.
                    if (data.result && data.result.state === 'accepted_pending_prewarm') {
                        stopPolling();
                        return runAcceptedPrewarm();
                    }
                    return json;
                }).then(function () {
                    prewarmActive = false;
                    running = false;
                    stopPolling();
                    window.setTimeout(function () { window.location.reload(); }, 900);
                }).catch(function () {
                    // Transport or parse errors are ambiguous until the server
                    // ledger has been checked. Explicit campaign errors are
                    // also reconciled with the authoritative terminal phase.
                    prewarmActive = false;
                    recoverAfterTransportLoss();
                });
            });
        });

        // A reload during a long request can reconnect to the short-lived local
        // ledger. Stale terminal state is intentionally not surfaced on load.
        request('yperf_autopilot_status').then(function (json) {
            var state = json && json.success && json.data ? json.data.progress : null;
            if (!state || state.terminal || !state.updated_at) { return; }
            if ((Math.floor(Date.now() / 1000) - parseInt(state.updated_at, 10)) > 120) { return; }
            if (running || recoveringTransport) { return; }
            running = true;
            setButtonsBusy(true);
            render(state);
            if (state.phase === 'prewarm' || state.phase === 'prewarm_ready') {
                runAcceptedPrewarm().then(function () {
                    prewarmActive = false;
                    running = false;
                    stopPolling();
                    window.setTimeout(function () { window.location.reload(); }, 900);
                }).catch(function () {
                    prewarmActive = false;
                    running = false;
                    stopPolling();
                    setButtonsBusy(false);
                });
                return;
            }
            poll();
        }).catch(function () {});
    }());

    // Portable settings import is intentionally browser-side. The chosen JSON
    // is read locally and copied into the bounded form payload; YPerf never
    // accepts an arbitrary server filesystem path.
    (function initSettingsImportReader() {
        var fileInput = document.getElementById('yperf-settings-import-file');
        var textarea = document.getElementById('yperf-settings-import-json');
        var state = document.getElementById('yperf-settings-import-file-state');
        if (!fileInput || !textarea) { return; }
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            if (!file) {
                if (state) { state.textContent = yperfI18n('importNoFile', 'No file selected'); }
                return;
            }
            if (file.size > 262144) {
                textarea.value = '';
                fileInput.value = '';
                if (state) { state.textContent = yperfI18n('importFileTooLarge', 'File is larger than the 256 KB safety limit.'); }
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                textarea.value = typeof reader.result === 'string' ? reader.result : '';
                if (state) { state.textContent = file.name + yperfI18n('importReadyToVerify', ' · ready to verify'); }
            };
            reader.onerror = function () {
                textarea.value = '';
                if (state) { state.textContent = yperfI18n('importReadError', 'Could not read this file in the browser.'); }
            };
            reader.readAsText(file);
        });
    }());

    // Expert diagnostics behave like one coherent workspace: three compact
    // entry cards when closed, and a single full-width tool when opened.
    (function initExpertDiagnosticAccordion() {
        var grids = Array.prototype.slice.call(document.querySelectorAll('.yperf-expert-diagnostic-grid'));
        grids.forEach(function (grid) {
            var cards = Array.prototype.slice.call(grid.children).filter(function (node) { return node.matches && node.matches('details.yperf-expert-diagnostic-card'); });
            var firstOpenSeen = false;
            cards.forEach(function (card) {
                if (card.open) {
                    if (firstOpenSeen) { card.open = false; }
                    firstOpenSeen = true;
                }
                card.addEventListener('toggle', function () {
                    if (!card.open) { return; }
                    cards.forEach(function (other) {
                        if (other !== card && other.open) { other.open = false; }
                    });
                });
            });
        });
    }());

}());

/* YPerf 2.22.0 — immediate activity feedback for provider operations. */
(function () {
    'use strict';

    // UI-only gettext bridge: localize browser messages without translating protocol keys.
    function yperfI18n(key, fallback) {
        var strings = window.YPerfDashboard && window.YPerfDashboard.strings;
        return strings && typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }

    var forms = document.querySelectorAll('[data-yperf-cloudflare-action]');
    var progress = document.querySelector('[data-yperf-cloudflare-progress]');
    var progressTitle = progress ? progress.querySelector('[data-yperf-cloudflare-progress-title]') : null;
    var progressDetail = progress ? progress.querySelector('[data-yperf-cloudflare-progress-detail]') : null;
    if (!forms.length || !progress) { return; }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-yperf-submitting') === '1') { return; }
            event.preventDefault();
            form.setAttribute('data-yperf-submitting', '1');
            var button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; }
            if (progressTitle) { progressTitle.textContent = form.getAttribute('data-yperf-loading-title') || yperfI18n('cloudflareWorking', 'Working with Cloudflare…'); }
            if (progressDetail) { progressDetail.textContent = form.getAttribute('data-yperf-loading-detail') || yperfI18n('cloudflareKeepOpen', 'Please keep this page open while YPerf completes the request.'); }
            progress.hidden = false;
            progress.setAttribute('aria-busy', 'true');
            try { progress.scrollIntoView({block: 'nearest', behavior: 'smooth'}); } catch (e) {}
            window.setTimeout(function () { form.submit(); }, 80);
        });
    });
}());
