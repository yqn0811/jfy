const fs = require('fs')
const path = require('path')

const root = path.resolve(__dirname, '..')
const buildRoot = path.join(root, 'dist', 'build')
const distRoot = path.join(root, 'dist', 'build', 'mp-weixin')
const projectConfigPath = path.join(root, 'project.config.json')
const projectPrivateConfigPath = path.join(root, 'project.private.config.json')

if (!fs.existsSync(distRoot)) {
  throw new Error(`Build output not found: ${distRoot}`)
}

const projectConfig = JSON.parse(fs.readFileSync(projectConfigPath, 'utf8'))
projectConfig.projectname = projectConfig.projectname || 'jfyun-new'

const standaloneConfig = { ...projectConfig }
delete standaloneConfig.miniprogramRoot

const buildConfig = {
  ...projectConfig,
  miniprogramRoot: 'mp-weixin/'
}

fs.writeFileSync(path.join(distRoot, 'project.config.json'), `${JSON.stringify(standaloneConfig, null, 2)}\n`)
fs.writeFileSync(path.join(buildRoot, 'project.config.json'), `${JSON.stringify(buildConfig, null, 2)}\n`)

if (fs.existsSync(projectPrivateConfigPath)) {
  fs.copyFileSync(projectPrivateConfigPath, path.join(distRoot, 'project.private.config.json'))
  fs.copyFileSync(projectPrivateConfigPath, path.join(buildRoot, 'project.private.config.json'))
}
