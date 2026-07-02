const util = require('util')

if (typeof util.isRegExp !== 'function') {
  util.isRegExp = value => Object.prototype.toString.call(value) === '[object RegExp]'
}
