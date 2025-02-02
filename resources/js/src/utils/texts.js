export function cutText(text, length) {
  if (text.split(' ').length > 1) {
    const string = text.substring(0, length);
    const splitText = string.split(' ');
    splitText.pop();
    return splitText.join(' ') + '...';
  }
  return text;
}

export function capitalizeFirstLetter(string) {
  return string ? string.charAt(0).toUpperCase() + string.slice(1) : '';
}

export function getLastPart(str, splitter) {
  return str.split(splitter).pop();
}