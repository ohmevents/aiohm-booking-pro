const path = require('path');

module.exports = {
  entry: './assets/js/src/index.js',
  output: {
    filename: 'main.js',
    path: path.resolve(__dirname, 'assets/js/dist'),
  },
};
