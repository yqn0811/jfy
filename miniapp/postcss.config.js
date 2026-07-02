const path = require('path')

module.exports = {
  plugins: {
    'autoprefixer': {
      remove: process.env.UNI_PLATFORM !== 'h5'
    },
    '@dcloudio/vue-cli-plugin-uni/packages/postcss': {}
  }
}
