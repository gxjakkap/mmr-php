// MEMORii progressive enhancements. Every feature works without JavaScript; this only adds convenience.
(function () {
  'use strict';
  // 1) Client-side photo check (the server validates again by file content).
  document.querySelectorAll('input[type=file][data-max-bytes]').forEach(function (input) {
    input.addEventListener('change', function () {
      var max = +input.dataset.maxBytes, maxFiles = +input.dataset.maxFiles, msg = '';
      var ok = ['image/jpeg', 'image/png', 'image/webp'];
      if (input.files.length > maxFiles) msg = input.dataset.msgCount;
      Array.prototype.forEach.call(input.files, function (f) {
        if (ok.indexOf(f.type) < 0) msg = input.dataset.msgType;
        else if (f.size > max) msg = f.name + ': ' + input.dataset.msgSize;
      });
      input.setCustomValidity(msg);
      var out = document.getElementById(input.id + '-error');
      if (out) out.textContent = msg;
    });
  });

  // 2) Quotation builder: live estimate + add line. The server recomputes the real totals.
  var form = document.querySelector('form[data-quote]');
  if (form) {
    var vat = +form.dataset.vatBp, fmt = form.dataset.locale === 'en' ? 'en-US' : 'th-TH';
    var money = function (s) { return (s / 100).toLocaleString(fmt, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var recalc = function () {
      var sub = 0, disc = 0;
      form.querySelectorAll('.item-row').forEach(function (row) {
        var q = parseFloat(row.querySelector('[data-f=qty]').value) || 0;
        var p = Math.round((parseFloat(row.querySelector('[data-f=price]').value) || 0) * 100);
        var line = Math.round(q * p);
        if (row.querySelector('[data-f=kind]').value === 'discount') disc += line; else sub += line;
      });
      var taxable = Math.max(sub - disc, 0), tax = Math.round(taxable * vat / 10000);
      form.querySelector('[data-out=subtotal]').textContent = money(sub);
      form.querySelector('[data-out=discount]').textContent = money(disc);
      form.querySelector('[data-out=tax]').textContent = money(tax);
      form.querySelector('[data-out=total]').textContent = money(taxable + tax);
      form.querySelector('[data-out=warn]').hidden = disc <= sub;
    };
    form.addEventListener('input', recalc);
    var add = form.querySelector('[data-add-row]');
    if (add) {
      add.hidden = false;
      add.addEventListener('click', function () {
        var rows = form.querySelectorAll('.item-row');
        if (rows.length >= 30) return;
        var clone = rows[rows.length - 1].cloneNode(true);
        var i = rows.length;
        clone.querySelectorAll('input, select').forEach(function (el) {
          el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
          el.id = el.id.replace(/\d+$/, String(i));
          if (el.tagName === 'INPUT') el.value = el.dataset.f === 'qty' ? '1' : '';
        });
        clone.querySelectorAll('label').forEach(function (l) { l.htmlFor = l.htmlFor.replace(/\d+$/, String(i)); });
        rows[rows.length - 1].after(clone);
        recalc();
      });
    }
    recalc();
  }
})();
