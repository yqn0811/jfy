console.log('Start requiring webpack');
try {
  const webpack = require('webpack');
  console.log('Webpack required successfully');
  console.log('Webpack version:', webpack.version);
} catch (e) {
  console.error('Error requiring webpack:', e);
}
console.log('Done');
