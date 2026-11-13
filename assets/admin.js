import { speak } from '@wordpress/a11y';

const root = document.getElementById('components-overview-async');
if (root) {
  const config = JSON.parse(document.getElementById('wp-script-module-data-components-overview-flynt').textContent);
  const url = new URL(window.location.href);
  if (!url.searchParams.has('lang') && config.language) url.searchParams.set('lang', config.language);
  const endpoint = new URL(config.endpoint);
  // WPML redirects may decode nested query strings. A URL-safe payload survives that rewrite.
  endpoint.searchParams.set('view', btoa(url.searchParams.toString())
    .replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, ''));
  if (url.searchParams.has('lang')) endpoint.searchParams.set('lang', url.searchParams.get('lang'));
  endpoint.searchParams.set('_locale', 'user');
  endpoint.searchParams.set('ui_locale', config.locale);

  async function load(region) {
    if (!region) return;
    const filters = region.dataset.async === 'views';
    const body = region.querySelector('[data-body]');
    const error = region.querySelector('[data-error]');
    const label = region.querySelector('[data-initial-label]');
    const requestUrl = new URL(endpoint);
    requestUrl.searchParams.set('part', region.dataset.async);
    region.dataset.state = 'loading';
    region.setAttribute('aria-busy', 'true');
    body.hidden = false;
    error.hidden = true;

    try {
      const response = await fetch(requestUrl, {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': config.nonce, Accept: 'application/json' },
        cache: 'no-store'
      });
      const fallback = filters ? config.filtersError : config.error;
      const data = await response.json().catch(() => { throw new Error(fallback); });
      if (!response.ok) {
        const failure = new Error(typeof data.message === 'string' ? data.message : fallback);
        failure.code = data.code;
        throw failure;
      }
      if (typeof data.html !== 'string') throw new Error(fallback);
      // Keep the original tbody: WordPress binds its native mobile row toggles to that node.
      if (filters) {
        body.innerHTML = data.html;
      } else {
        const content = document.createElement('template');
        content.innerHTML = data.html;
        const rows = body.querySelector('#the-list');
        const nextRows = content.content.querySelector('#the-list');
        if (!rows || !nextRows) throw new Error(fallback);
        rows.innerHTML = nextRows.innerHTML;
        nextRows.replaceWith(rows);
        body.replaceChildren(content.content);
      }
      region.dataset.state = 'loaded';
      speak(filters ? config.filtersLoaded : config.loaded);
    } catch (failure) {
      region.dataset.state = 'error';
      const message = failure instanceof TypeError
        ? (filters ? config.filtersError : config.error) : failure.message;
      const expired = ['components_overview_expired_view', 'rest_cookie_invalid_nonce'].includes(failure.code);
      const recovery = expired ? config.reopen : config.reload;
      error.querySelector('[data-error-message]').textContent = `${message} ${recovery}`;
      error.hidden = false;
      if (filters) body.hidden = true;
      if (label) label.textContent = config.notLoaded;
    } finally {
      region.setAttribute('aria-busy', 'false');
    }
  }

  // Keep the browser's normal GET submission, omitting stale pagination for a new search.
  root.querySelector('form')?.addEventListener('formdata', (event) => {
    if (!document.activeElement?.classList.contains('current-page')) event.formData.delete('paged');
    event.formData.delete('_wpnonce');
    event.formData.delete('_wp_http_referer');
  });

  // Start counts after the overview rows; a failed rebuild must not be repeated by the filters.
  const table = root.querySelector('[data-async="table"]');
  const views = root.querySelector('[data-async="views"]');
  void load(table).then(() => {
    if (table && table.dataset.state !== 'loaded') {
      if (views) {
        views.dataset.state = 'error';
        views.setAttribute('aria-busy', 'false');
        views.querySelector('[data-body]').hidden = true;
      }
      return;
    }
    return load(views);
  });
}
