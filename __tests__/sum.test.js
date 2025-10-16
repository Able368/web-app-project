const sum = require('../src/sum');

describe('sum()', () => {
  test('adds two numbers', () => {
    expect(sum(2,3)).toBe(5);
  });

  test('works with negative numbers', () => {
    expect(sum(-1,1)).toBe(0);
  });
});
