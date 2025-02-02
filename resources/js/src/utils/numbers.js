export function randomNumbers(from, to, length) {
  const numbers = [0]; // Ensures the array always starts with 0
  for (let i = 1; i < length; i++) {
    numbers.push(Math.ceil(Math.random() * (from - to) + to));
  }
  return numbers;
}