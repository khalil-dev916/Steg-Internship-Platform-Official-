// Lightweight table utilities: sorting + pagination
// Usage:
//  const t = new TableUtils(document.querySelector('table.data-table'), { pageSize: 10 });
//  t.init();

class TableUtils {
  constructor(table, { pageSize = 10 } = {}) {
    this.table = table;
    this.tbody = table?.querySelector('tbody');
    this.headers = Array.from(table?.querySelectorAll('thead th') || []);
    this.rows = [];
    this.sortState = { index: -1, dir: 'asc' };
    this.pageSize = pageSize;
    this.currentPage = 1;
    this.pager = null;
  this.exportScope = 'page'; // 'page' | 'all'
  }

  init() {
    if (!this.table || !this.tbody) return;
    this.refreshRows();
    this.setupSorting();
    this.setupPagination();
    this.renderPage(1);
  }

  refreshRows() {
    this.rows = Array.from(this.tbody.querySelectorAll('tr'));
  }

  setupSorting() {
    this.headers.forEach((th, index) => {
      th.style.cursor = 'pointer';
      th.setAttribute('aria-sort', 'none');
      // ensure indicator span exists
      let ind = th.querySelector('.sort-indicator');
      if (!ind) {
        ind = document.createElement('span');
        ind.className = 'sort-indicator';
        th.appendChild(ind);
      }
      th.addEventListener('click', () => {
        const dir = (this.sortState.index === index && this.sortState.dir === 'asc') ? 'desc' : 'asc';
        this.sort(index, dir);
      });
    });
  }

  sort(index, dir = 'asc') {
    this.refreshRows();
    const multiplier = dir === 'asc' ? 1 : -1;
    this.rows.sort((a, b) => {
      const ta = a.children[index]?.textContent.trim().toLowerCase() || '';
      const tb = b.children[index]?.textContent.trim().toLowerCase() || '';
      if (!isNaN(ta) && !isNaN(tb)) return (Number(ta) - Number(tb)) * multiplier;
      return ta.localeCompare(tb) * multiplier;
    });
    this.tbody.innerHTML = '';
    this.rows.forEach(r => this.tbody.appendChild(r));
    this.sortState = { index, dir };
    // update indicators
    this.headers.forEach((th, i) => {
      const ind = th.querySelector('.sort-indicator');
      if (i === index) {
        th.setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');
        if (ind) ind.textContent = dir === 'asc' ? '▲' : '▼';
      } else {
        th.setAttribute('aria-sort', 'none');
        if (ind) ind.textContent = '';
      }
    });
    this.renderPage(1);
  }

  setupPagination() {
    // Create pager element if not present
    this.pager = this.table.nextElementSibling;
    if (!this.pager || !this.pager.classList.contains('pager')) {
      this.pager = document.createElement('div');
      this.pager.className = 'pager';
      this.table.insertAdjacentElement('afterend', this.pager);
    }
  }

  renderPage(page) {
    this.refreshRows();
    const total = this.rows.length;
    const pages = Math.max(1, Math.ceil(total / this.pageSize));
    this.currentPage = Math.min(Math.max(1, page), pages);

    // Show only rows for current page
    const start = (this.currentPage - 1) * this.pageSize;
    const end = start + this.pageSize;
    this.rows.forEach((row, i) => {
      row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // Render pager controls
    this.pager.innerHTML = '';
    const info = document.createElement('span');
    info.textContent = `Page ${this.currentPage}/${pages}`;

    const prev = document.createElement('button');
    prev.className = 'btn small';
    prev.textContent = 'Précédent';
    prev.disabled = this.currentPage === 1;
    prev.addEventListener('click', () => this.renderPage(this.currentPage - 1));

    const next = document.createElement('button');
    next.className = 'btn small';
    next.textContent = 'Suivant';
    next.disabled = this.currentPage === pages;
    next.addEventListener('click', () => this.renderPage(this.currentPage + 1));

    const sizeSel = document.createElement('select');
    [5,10,20,50].forEach(sz => {
      const opt = document.createElement('option');
      opt.value = String(sz);
      opt.textContent = `${sz}/page`;
      if (sz === this.pageSize) opt.selected = true;
      sizeSel.appendChild(opt);
    });
    sizeSel.addEventListener('change', () => {
      this.pageSize = Number(sizeSel.value);
      this.renderPage(1);
    });

    // Export controls
    const scopeSel = document.createElement('select');
    ;[
      { v: 'page', t: 'Page actuelle' },
      { v: 'all', t: 'Toutes les lignes' }
    ].forEach(({v,t}) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = t;
      if (v === this.exportScope) opt.selected = true;
      scopeSel.appendChild(opt);
    });
    scopeSel.addEventListener('change', () => {
      this.exportScope = scopeSel.value;
    });

    const exportBtn = document.createElement('button');
    exportBtn.className = 'btn small';
    exportBtn.textContent = 'Exporter CSV';
    exportBtn.addEventListener('click', () => this.exportCSV(this.exportScope));

    const rightGroup = document.createElement('span');
    rightGroup.style.marginLeft = 'auto';
    rightGroup.style.display = 'inline-flex';
    rightGroup.style.gap = '8px';
    rightGroup.append(sizeSel, scopeSel, exportBtn);

    this.pager.append(prev, info, next, rightGroup);
  }

  // Create and download a CSV file of the current table view
  exportCSV(scope = 'page') {
    // Collect header titles
    const headers = this.headers.map(th => th.cloneNode(true))
      .map(el => {
        // remove sort indicator text
        const ind = el.querySelector('.sort-indicator');
        if (ind) ind.remove();
        return el.textContent.trim();
      });

    // Determine row indices to export
    let rowSlice = [];
    if (scope === 'page') {
      const start = (this.currentPage - 1) * this.pageSize;
      const end = start + this.pageSize;
      rowSlice = this.rows.slice(start, end);
    } else {
      rowSlice = this.rows.slice();
    }

    const rows = rowSlice.map(tr => Array.from(tr.children).map(td => td.textContent.trim()));

    const csv = [headers, ...rows].map(cols => cols.map(v => this.csvEscape(v)).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const ts = new Date();
    const pad = n => n.toString().padStart(2, '0');
    const fname = `export_table_${ts.getFullYear()}${pad(ts.getMonth()+1)}${pad(ts.getDate())}_${pad(ts.getHours())}${pad(ts.getMinutes())}${pad(ts.getSeconds())}.csv`;
    a.href = url;
    a.download = fname;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  csvEscape(value) {
    if (value == null) return '';
    const str = String(value).replace(/\r?\n|\r/g, ' ');
    // escape quotes by doubling them
    const needsQuote = /[",\n]/.test(str) || str.includes(',') || str.includes(' ');
    const escaped = str.replace(/"/g, '""');
    return needsQuote ? `"${escaped}"` : escaped;
  }
}

// Auto-init tables with data-table & data-enhanced attribute
window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('table.data-table[data-enhanced]')
    .forEach(tbl => new TableUtils(tbl, { pageSize: Number(tbl.dataset.pageSize) || 10 }).init());
});

// Expose globally in case it's needed manually
window.TableUtils = TableUtils;
