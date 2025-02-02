export function toRGB(colors) {
  const tempColors = Object.assign({}, colors);
  const rgbColors = Object.entries(tempColors);
  
  for (const [key, value] of rgbColors) {
    if (typeof value === 'string' && value.replace('#', '').length === 6) {
      const aRgbHex = value.replace('#', '').match(/.{1,2}/g);
      tempColors[key] = (opacity = 1) =>
        `rgb(${parseInt(aRgbHex[0], 16)} ${parseInt(aRgbHex[1], 16)} ${parseInt(aRgbHex[2], 16)} / ${opacity})`;
    } else {
      tempColors[key] = toRGB(value); // Recursively convert nested objects
    }
  }

  return tempColors;
}