const fs = require('fs')
const path = require('path')

const root = path.resolve(__dirname, '..')
const buildRoot = path.join(root, 'dist', 'build')
const distRoot = path.join(root, 'dist', 'build', 'mp-weixin')
const previewRoot = path.join(root, 'dist', 'wechat-preview', 'mp-weixin')
const projectConfigPath = path.join(root, 'project.config.json')
const projectPrivateConfigPath = path.join(root, 'project.private.config.json')

if (!fs.existsSync(distRoot)) {
  throw new Error(`Build output not found: ${distRoot}`)
}

const projectConfig = JSON.parse(fs.readFileSync(projectConfigPath, 'utf8'))
projectConfig.projectname = projectConfig.projectname || 'jfyun-new'

const normalizeWechatProjectConfig = (config) => {
  config.setting = {
    ...(config.setting || {}),
    es6: false,
    postcss: false,
    minified: false,
    minifyWXSS: false,
    minifyWXML: false,
    uploadWithSourceMap: false
  }
  return config
}

const standaloneConfig = { ...projectConfig }
delete standaloneConfig.miniprogramRoot

const buildConfig = {
  ...projectConfig,
  miniprogramRoot: 'mp-weixin/'
}

const loadPrivateConfig = () => {
  if (!fs.existsSync(projectPrivateConfigPath)) {
    return null
  }
  return JSON.parse(fs.readFileSync(projectPrivateConfigPath, 'utf8'))
}

const copyDir = (src, dest) => {
  fs.rmSync(dest, { recursive: true, force: true })
  fs.mkdirSync(dest, { recursive: true })
  fs.cpSync(src, dest, { recursive: true })
}

const writeJson = (filePath, value) => {
  fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`)
}

writeJson(path.join(distRoot, 'project.config.json'), normalizeWechatProjectConfig(standaloneConfig))
writeJson(path.join(buildRoot, 'project.config.json'), normalizeWechatProjectConfig(buildConfig))

const privateConfig = loadPrivateConfig()
if (privateConfig) {
  const standalonePrivateConfig = { ...privateConfig }
  delete standalonePrivateConfig.miniprogramRoot

  const buildPrivateConfig = {
    ...privateConfig,
    miniprogramRoot: 'mp-weixin/'
  }

  writeJson(path.join(distRoot, 'project.private.config.json'), normalizeWechatProjectConfig(standalonePrivateConfig))
  writeJson(path.join(buildRoot, 'project.private.config.json'), normalizeWechatProjectConfig(buildPrivateConfig))
}

copyDir(distRoot, previewRoot)
