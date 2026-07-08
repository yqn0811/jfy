const util = require('util')

if (util.isArray !== Array.isArray) {
  util.isArray = Array.isArray
}

if (typeof util.isRegExp !== 'function') {
  util.isRegExp = value => Object.prototype.toString.call(value) === '[object RegExp]'
}
