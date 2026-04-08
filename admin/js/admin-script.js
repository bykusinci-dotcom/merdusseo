/* =============================================================
   MerdusSEO Admin Script  –  v1.0.0
   ============================================================= */
(function ($) {
  'use strict';

  const ajaxUrl = merdusSEO.ajaxUrl;
  const nonce   = merdusSEO.nonce;

  /* ─── Toast ───────────────────────────────────────────────── */
  function toast(msg, type = 'success', ms = 4000) {
    let $wrap = $('#mseo-toast');
    if (!$wrap.length) {
      $wrap = $('<div id="mseo-toast"></div>').appendTo('body');
    }
    const $t = $('<div class="mseo-toast mseo-toast--' + type + '">' + msg + '</div>').appendTo($wrap);
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), ms);
  }

  /* ─── AJAX helper ─────────────────────────────────────────── */
  function ajax(action, data, opts) {
    const defaults = { method: 'POST', url: ajaxUrl };
    return $.ajax(Object.assign(defaults, opts, {
      data: Object.assign({ action, _ajax_nonce: nonce }, data),
    }));
  }

  /* ─── Char counter utility ────────────────────────────────── */
  function updateCounter($input, $counter, $bar, max) {
    const len = $input.val().length;
    const pct = Math.min((len / max) * 100, 100);
    $counter.text(len + ' / ' + max);
    $bar.css('width', pct + '%');
    $bar.removeClass('mseo-char-bar__fill--warn mseo-char-bar__fill--danger');
    if (pct > 100) $bar.addClass('mseo-char-bar__fill--danger');
    else if (pct > 85) $bar.addClass('mseo-char-bar__fill--warn');
  }

  /* ─── Issue badge helper ──────────────────────────────────── */
  const issueLabels = {
    missing_title:     { text: 'Eksik Title',       cls: '' },
    long_title:        { text: 'Uzun Title',         cls: '--warning' },
    short_title:       { text: 'Kısa Title',         cls: '--warning' },
    missing_desc:      { text: 'Eksik Açıklama',    cls: '' },
    long_desc:         { text: 'Uzun Açıklama',     cls: '--warning' },
    short_desc:        { text: 'Kısa Açıklama',     cls: '--warning' },
    duplicate_h1_title:{ text: 'Duplicate H1/Title', cls: '--info' },
  };

  function issueBadge(issue) {
    const meta = issueLabels[issue] || { text: issue, cls: '' };
    return '<span class="mseo-issue-badge' + meta.cls + '">' + meta.text + '</span>';
  }

  /* ═══════════════════════════════════════════════════════════
     META ANALYSIS PAGE
     ═══════════════════════════════════════════════════════════ */
  let metaResults = [];

  /* ── Scan ──────────────────────────────────────────────────── */
  $('#btn-scan-meta').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true).text('Taranıyor...');
    $('#meta-progress').show();
    $('#meta-empty, #meta-table-wrap').hide();

    ajax('merdusseo_scan_meta', {}).done(function (res) {
      if (!res.success) { toast(res.data.message, 'error'); return; }

      metaResults = res.data.results;
      updateMetaSummary(res.data.summary);
      renderMetaTable(metaResults);
      $('#meta-progress').hide();
      $('#meta-table-wrap').show();
      $('#meta-summary').show();
      toast('Tarama tamamlandı. ' + metaResults.length + ' sayfa analiz edildi.');
    }).fail(function () {
      toast('Tarama sırasında bir hata oluştu.', 'error');
    }).always(function () {
      $btn.prop('disabled', false).html(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg> Tüm Siteyi Tara'
      );
    });
  });

  /* ── Summary ───────────────────────────────────────────────── */
  function updateMetaSummary(s) {
    $('#sum-ok').text(s.ok);
    $('#sum-issues').text(s.issues);
    $('#sum-missing-title').text(s.counts.missing_title);
    $('#sum-long-title').text(s.counts.long_title + (s.counts.short_title ? '+' + s.counts.short_title : ''));
    $('#sum-missing-desc').text(s.counts.missing_desc);
    $('#sum-long-desc').text(s.counts.long_desc + (s.counts.short_desc ? '+' + s.counts.short_desc : ''));
    $('#sum-duplicate').text(s.counts.duplicate_h1_title);
  }

  /* ── Render table ──────────────────────────────────────────── */
  function renderMetaTable(rows) {
    const $tbody = $('#meta-table-body').empty();

    rows.forEach(function (r) {
      const issues = r.issues.map(issueBadge).join(' ');
      const titleOk = !r.issues.some(i => i.includes('title'));
      const descOk  = !r.issues.some(i => i.includes('desc'));

      const row = `
        <tr class="${r.issues.length ? 'mseo-row--broken' : ''}"
            data-issues="${r.issues.join(' ')}"
            data-title="${escHtml(r.title).toLowerCase()}"
            data-post-id="${r.post_id}">
          <td>
            <div style="font-weight:600;margin-bottom:3px;">
              <a href="${escHtml(r.url)}" target="_blank" class="mseo-link">${escHtml(r.title)}</a>
            </div>
            <div style="font-size:11px;color:var(--mseo-muted)">${escHtml(r.type)}</div>
          </td>
          <td>
            <div style="${titleOk ? '' : 'color:var(--mseo-danger)'}">${escHtml(r.meta_title || '—')}</div>
            <div style="font-size:11px;color:var(--mseo-muted)">${r.title_len} karakter</div>
          </td>
          <td>
            <div style="${descOk ? '' : 'color:var(--mseo-danger)'}">${escHtml((r.meta_desc || '').substring(0,80) + (r.meta_desc && r.meta_desc.length>80 ? '…' : ''))}</div>
            <div style="font-size:11px;color:var(--mseo-muted)">${r.desc_len} karakter</div>
          </td>
          <td>${escHtml(r.h1 || '—')}</td>
          <td>${issues || '<span class="mseo-badge mseo-badge--success">OK</span>'}</td>
          <td>
            <button class="mseo-btn mseo-btn--secondary mseo-btn--sm btn-edit-meta"
                    data-post-id="${r.post_id}"
                    data-title="${escHtml(r.meta_title)}"
                    data-desc="${escHtml(r.meta_desc)}"
                    data-h1="${escHtml(r.h1)}"
                    data-post-name="${escHtml(r.title)}">
              Düzenle
            </button>
          </td>
        </tr>`;
      $tbody.append(row);
    });
  }

  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Filter & search ───────────────────────────────────────── */
  function applyMetaFilters() {
    const filterVal = $('#meta-filter-type').val();
    const search    = $('#meta-search').val().toLowerCase();

    $('#meta-table-body tr').each(function () {
      const $tr     = $(this);
      const issues  = $tr.attr('data-issues') || '';
      const title   = $tr.attr('data-title') || '';
      const matchF  = !filterVal || issues.includes(filterVal);
      const matchS  = !search || title.includes(search);
      $tr.toggle(matchF && matchS);
    });
  }

  $('#meta-filter-type').on('change', applyMetaFilters);
  $('#meta-search').on('input', applyMetaFilters);

  /* ── Edit modal ────────────────────────────────────────────── */
  $(document).on('click', '.btn-edit-meta', function () {
    const $btn = $(this);
    $('#edit-post-id').val($btn.data('post-id'));
    $('#edit-meta-title').val($btn.data('title'));
    $('#edit-meta-desc').val($btn.data('desc'));
    $('#edit-h1').val($btn.data('h1'));
    $('#edit-modal-title').text('Meta Düzenle – ' + $btn.data('post-name'));
    $('#title-suggestions, #desc-suggestions').hide().empty();
    updateCounter($('#edit-meta-title'), $('#title-counter'), $('#title-bar'), 60);
    updateCounter($('#edit-meta-desc'), $('#desc-counter'), $('#desc-bar'), 160);
    $('#edit-modal').fadeIn(150);
  });

  $('#edit-modal-close, #edit-modal-cancel').on('click', () => $('#edit-modal').fadeOut(150));
  $('#edit-modal-overlay').on('click', function(e) { if ($(e.target).is('#edit-modal')) $('#edit-modal').fadeOut(150); });

  /* Char counters in modal */
  $('#edit-meta-title').on('input', function () {
    updateCounter($(this), $('#title-counter'), $('#title-bar'), 60);
  });
  $('#edit-meta-desc').on('input', function () {
    updateCounter($(this), $('#desc-counter'), $('#desc-bar'), 160);
  });

  /* ── Save meta ──────────────────────────────────────────────── */
  $('#edit-modal-save').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true).text('Kaydediliyor...');

    ajax('merdusseo_save_meta', {
      post_id:    $('#edit-post-id').val(),
      meta_title: $('#edit-meta-title').val(),
      meta_desc:  $('#edit-meta-desc').val(),
    }).done(function (res) {
      if (res.success) {
        toast(res.data.message);
        $('#edit-modal').fadeOut(150);
      } else {
        toast(res.data.message, 'error');
      }
    }).fail(() => toast('Kayıt sırasında bir hata oluştu.', 'error'))
      .always(() => $btn.prop('disabled', false).text('Kaydet'));
  });

  /* ── AI suggest ────────────────────────────────────────────── */
  $(document).on('click', '.mseo-btn--ai', function () {
    const field   = $(this).data('field');
    const postId  = $('#edit-post-id').val();
    const $wrap   = field === 'title' ? $('#title-suggestions') : $('#desc-suggestions');
    const $input  = field === 'title' ? $('#edit-meta-title') : $('#edit-meta-desc');
    const maxLen  = field === 'title' ? 60 : 160;
    const $bar    = field === 'title' ? $('#title-bar') : $('#desc-bar');
    const $counter= field === 'title' ? $('#title-counter') : $('#desc-counter');
    const $btn    = $(this);

    $btn.prop('disabled', true);
    $wrap.show().html('<div class="mseo-ai-loading">⭐ AI önerileri yükleniyor...</div>');

    ajax('merdusseo_ai_suggest', { post_id: postId, field }).done(function (res) {
      if (!res.success) {
        $wrap.html('<div class="mseo-ai-loading" style="color:var(--mseo-danger)">' + res.data.message + '</div>');
        return;
      }
      const suggestions = res.data.suggestions;
      let html = '';
      suggestions.forEach(function (s) {
        const len = s.length;
        const cls = len > maxLen ? 'style="color:var(--mseo-danger)"' : (len < maxLen * .6 ? 'style="color:var(--mseo-warning)"' : '');
        html += `<div class="mseo-suggestion" data-text="${escHtml(s)}">
          <div class="mseo-suggestion__text">${escHtml(s)}</div>
          <div class="mseo-suggestion__len" ${cls}>${len} kar.</div>
        </div>`;
      });
      $wrap.html(html);

      /* Click to apply */
      $wrap.find('.mseo-suggestion').on('click', function () {
        $input.val($(this).data('text'));
        updateCounter($input, $counter, $bar, maxLen);
        $wrap.hide().empty();
      });
    }).fail(() => {
      $wrap.html('<div class="mseo-ai-loading" style="color:var(--mseo-danger)">Bir hata oluştu, tekrar deneyin.</div>');
    }).always(() => $btn.prop('disabled', false));
  });

  /* ── Export CSV ─────────────────────────────────────────────── */
  $('#btn-export-csv').on('click', function () {
    window.location.href = merdusSEO.exportUrl;
  });

  /* ── Import CSV ─────────────────────────────────────────────── */
  $('#csv-import-input').on('change', function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'merdusseo_import_csv');
    formData.append('_ajax_nonce', nonce);
    formData.append('csv_file', file);

    toast('CSV içe aktarılıyor...', 'info');

    $.ajax({
      url: ajaxUrl,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).done(function (res) {
      if (res.success) {
        toast(res.data.message);
        if (res.data.errors && res.data.errors.length) {
          res.data.errors.forEach(e => toast(e, 'error', 8000));
        }
      } else {
        toast(res.data.message, 'error');
      }
    }).fail(() => toast('İçe aktarma sırasında hata oluştu.', 'error'));

    /* Reset input */
    $(this).val('');
  });

  /* ═══════════════════════════════════════════════════════════
     LINK CHECKER PAGE
     ═══════════════════════════════════════════════════════════ */

  /* ── Scan links ────────────────────────────────────────────── */
  $('#btn-scan-links').on('click', function () {
    const $btn = $(this);
    if (!confirm('Link taraması sitenizin büyüklüğüne bağlı olarak birkaç dakika sürebilir. Devam etmek istiyor musunuz?')) return;

    $btn.prop('disabled', true).text('Taranıyor...');
    $('#link-progress').show();

    ajax('merdusseo_scan_links', {}, { timeout: 600000 }).done(function (res) {
      if (res.success) {
        toast('Tarama tamamlandı. ' + res.data.count + ' link kontrol edildi.');
        setTimeout(() => location.reload(), 1500);
      } else {
        toast(res.data.message, 'error');
      }
    }).fail(function () {
      toast('Tarama sırasında bir hata oluştu.', 'error');
    }).always(function () {
      $('#link-progress').hide();
      $btn.prop('disabled', false).html(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg> Tüm Linkleri Tara'
      );
    });
  });

  /* ── Remove broken links ───────────────────────────────────── */
  $('#btn-remove-broken').on('click', function () {
    const brokenCount = parseInt($('#broken-count').text(), 10);
    if (!confirm(brokenCount + ' adet kırık link post içeriklerinden kaldırılacak. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?')) return;

    const $btn = $(this);
    $btn.prop('disabled', true).text('Kaldırılıyor...');

    ajax('merdusseo_remove_broken_links', {}).done(function (res) {
      if (res.success) {
        toast(res.data.removed + ' kırık link başarıyla kaldırıldı.');
        setTimeout(() => location.reload(), 2000);
      } else {
        toast(res.data.message, 'error');
      }
    }).fail(() => toast('Bir hata oluştu.', 'error'))
      .always(() => $btn.prop('disabled', false));
  });

  /* ── Link filter & search ──────────────────────────────────── */
  function applyLinkFilters() {
    const filter = $('#link-filter').val();
    const search = $('#link-search').val().toLowerCase();

    $('#link-table-body tr').each(function () {
      const $tr    = $(this);
      const type   = $tr.data('type');
      const broken = $tr.data('broken');
      const src    = $tr.data('source') || '';
      const url    = $tr.data('url') || '';

      let matchF = true;
      if (filter === 'broken')   matchF = broken == 1;
      if (filter === 'internal') matchF = type === 'internal';
      if (filter === 'external') matchF = type === 'external';

      const matchS = !search || src.includes(search) || url.includes(search);
      $tr.toggle(matchF && matchS);
    });
  }

  $('#link-filter').on('change', applyLinkFilters);
  $('#link-search').on('input', applyLinkFilters);

  /* ═══════════════════════════════════════════════════════════
     KEYWORD CANNIBALIZATION PAGE
     ═══════════════════════════════════════════════════════════ */

  $('#btn-scan-cannibalization').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true).text('Analiz ediliyor...');
    $('#can-progress').show();

    ajax('merdusseo_scan_cannibalization', {}).done(function (res) {
      if (!res.success) { toast(res.data.message, 'error'); return; }

      toast('Analiz tamamlandı. ' + res.data.summary.groups + ' çakışma grubu bulundu.');
      setTimeout(() => location.reload(), 1500);
    }).fail(() => toast('Analiz sırasında bir hata oluştu.', 'error'))
      .always(function () {
        $('#can-progress').hide();
        $btn.prop('disabled', false).html(
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg> Blog Yazılarını Tara'
        );
      });
  });

  /* Keyword search in cannibalization */
  $('#can-search').on('input', function () {
    const q = $(this).val().toLowerCase();
    $('.mseo-can-group').each(function () {
      const kw = $(this).data('keyword') || '';
      $(this).toggle(!q || kw.includes(q));
    });
  });

  /* ═══════════════════════════════════════════════════════════
     SETTINGS PAGE
     ═══════════════════════════════════════════════════════════ */

  /* AI provider toggle */
  $('#ai-provider').on('change', function () {
    const val = $(this).val();
    $('#openai-models').toggleClass('mseo-hidden', val !== 'openai');
    $('#anthropic-models').toggleClass('mseo-hidden', val !== 'anthropic');
  });

  /* Toggle API key visibility */
  $('#toggle-api-key').on('click', function () {
    const $input = $('[name="merdusseo_ai_api_key"]');
    const isPass = $input.attr('type') === 'password';
    $input.attr('type', isPass ? 'text' : 'password');
    $(this).text(isPass ? 'Gizle' : 'Göster');
  });

  /* Settings form submit */
  $('#settings-form').on('submit', function (e) {
    e.preventDefault();

    const $btn  = $(this).find('[type="submit"]');
    const data  = {};
    const $form = $(this);

    /* Serialize all named fields */
    $form.find('[name]').each(function () {
      const name = $(this).attr('name');
      if ($(this).is(':checkbox')) {
        if (!data[name]) data[name] = [];
        if ($(this).is(':checked')) data[name].push($(this).val());
      } else if ($(this).is('select[id="model-openai"]') || $(this).is('select[id="model-anthropic"]')) {
        /* Only send the visible one */
        if (!$(this).closest('div').hasClass('mseo-hidden')) {
          data['merdusseo_ai_model'] = $(this).val();
        }
      } else {
        data[name] = $(this).val();
      }
    });

    $btn.prop('disabled', true).text('Kaydediliyor...');

    ajax('merdusseo_save_settings', data).done(function (res) {
      const $notice = $('#settings-notice');
      if (res.success) {
        $notice.attr('class', 'mseo-notice mseo-notice--success').text(res.data.message).show();
        toast(res.data.message);
      } else {
        $notice.attr('class', 'mseo-notice mseo-notice--error').text(res.data.message).show();
        toast(res.data.message, 'error');
      }
      setTimeout(() => $notice.fadeOut(), 4000);
    }).fail(() => toast('Bir hata oluştu.', 'error'))
      .always(function () {
        $btn.prop('disabled', false).html(
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Ayarları Kaydet'
        );
      });
  });

})(jQuery);
