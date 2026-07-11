const path = require('path')

module.exports = {
  transpileDependencies: ['uview-ui'],
  configureWebpack: config => {
    if (process.env.NODE_ENV === 'production') {
      const minimizer = config.optimization && config.optimization.minimizer
      if (Array.isArray(minimizer)) {
        minimizer.forEach(plugin => {
          const options = plugin && plugin.options
          if (options && options.terserOptions) {
            options.terserOptions.compress = {
              ...(options.terserOptions.compress || {}),
              drop_console: true,
              drop_debugger: true
            }
          }
        })
      }
    }
  },
  chainWebpack: config => {
    // Fix postcss-loader options for v4 compatibility
    // vue-cli-service 4 uses postcss-loader 3 syntax, but we are using postcss-loader 4 (required by uni-app)
    ['css', 'postcss', 'scss', 'sass', 'less', 'stylus'].forEach(rule => {
      const ruleObj = config.module.rule(rule)
      if (ruleObj) {
        ruleObj.oneOfs.store.forEach(oneOf => {
          if (oneOf.uses.has('postcss-loader')) {
            oneOf.use('postcss-loader').tap(options => {
              // Extract plugins and other v3 options
              const { plugins, config, ...rest } = options || {}

              // Construct v4 options
              // We ignore 'config' object from v3 because v4 expects string/boolean
              // We rely on postcss.config.js being present in root
              const newOptions = {
                postcssOptions: {
                  ...(plugins ? { plugins } : {})
                },
                ...rest
              }

              return newOptions
            })
          }
        })
      }
    })
  }
}
