export function formatCurrency(num) {
  if (!num) return '';

  const formattedNumber = num.toString().replace(/\D/g, '');
  const rest = formattedNumber.length % 3;
  let currency = formattedNumber.substr(0, rest);
  const thousand = formattedNumber.substr(rest).match(/\d{3}/g);

  if (thousand) {
    const separator = rest ? '.' : '';
    currency += separator + thousand.join('.');
  }

  return currency;
}

export function formatterCurrency(amount) {
  return new Intl.NumberFormat('nl-BE', { style: 'currency', currency: 'EUR' }).format(amount);
}

export function formatDecimal(decimal, format) {
  return new Intl.NumberFormat(format, { style: 'decimal', minimumFractionDigits: 2 }).format(decimal);
}