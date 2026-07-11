const fs = require('fs')
const path = require('path')
const childProcess = require('child_process')

const root = path.resolve(__dirname, '..')
const buildRoot = path.join(root, 'dist', 'build')
const distRoot = path.join(root, 'dist', 'build', 'mp-weixin')
const previewRoot = path.join(root, 'dist', 'wechat-preview', 'mp-weixin')
const projectConfigPath = path.join(root, 'project.config.json')
const projectPrivateConfigPath = path.join(root, 'project.private.config.json')
const subpackageRootName = 'pagesOther'
const staticRootName = 'static'
const textFileExtensions = new Set(['.js', '.json', '.wxml', '.wxss', '.wxs', '.css'])
const assetFileExtensions = new Set([
  '.png',
  '.jpg',
  '.jpeg',
  '.webp',
  '.gif',
  '.svg',
  '.mp3',
  '.mp4',
  '.wav',
  '.m4a'
])
const pngColorLimit = '48'
const jpegQuality = '70'

if (!fs.existsSync(distRoot)) {
  throw new Error(`Build output not found: ${distRoot}`)
}

const projectConfig = JSON.parse(fs.readFileSync(projectConfigPath, 'utf8'))
projectConfig.projectname = projectConfig.projectname || 'jfyun-new'

const normalizeWechatProjectConfig = (config) => {
  config.setting = {
    ...(config.setting || {}),
    es6: true,
    postcss: true,
    minified: true,
    minifyWXSS: true,
    minifyWXML: true,
    ignoreDevUnusedFiles: true,
    ignoreUploadUnusedFiles: true,
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

const toPosix = (value) => value.split(path.sep).join('/')

const walkFiles = (dir) => {
  if (!fs.existsSync(dir)) {
    return []
  }

  const files = []
  for (const name of fs.readdirSync(dir)) {
    const filePath = path.join(dir, name)
    const stat = fs.statSync(filePath)
    if (stat.isDirectory()) {
      files.push(...walkFiles(filePath))
    } else {
      files.push(filePath)
    }
  }
  return files
}

const ensureDir = (dir) => {
  fs.mkdirSync(dir, { recursive: true })
}

const isTextFile = (filePath) => textFileExtensions.has(path.extname(filePath).toLowerCase())

const isAssetFile = (filePath) => assetFileExtensions.has(path.extname(filePath).toLowerCase())

const removeEmptyDirectories = (dir, keepRoot = dir) => {
  if (!fs.existsSync(dir) || !fs.statSync(dir).isDirectory()) {
    return
  }

  for (const name of fs.readdirSync(dir)) {
    const child = path.join(dir, name)
    if (fs.existsSync(child) && fs.statSync(child).isDirectory()) {
      removeEmptyDirectories(child, keepRoot)
    }
  }

  if (dir !== keepRoot && fs.existsSync(dir) && fs.readdirSync(dir).length === 0) {
    fs.rmdirSync(dir)
  }
}

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

const rewriteStaticAssetReference = (content, assetSubPath, replacementPath) => {
  const staticPath = `${staticRootName}/${assetSubPath}`
  const pattern = new RegExp(
    `(^|[^A-Za-z0-9_/-])((?:@/|/|(?:\\.\\./)+)?${escapeRegExp(staticPath)})`,
    'g'
  )
  return content.replace(pattern, (match, prefix) => `${prefix}${replacementPath}`)
}

const replaceAssetReferences = (packageRoot, fromAsset, toAsset) => {
  for (const filePath of walkFiles(packageRoot).filter(isTextFile)) {
    const content = fs.readFileSync(filePath, 'utf8')
    const nextContent = content.split(fromAsset).join(toAsset)
    if (nextContent !== content) {
      fs.writeFileSync(filePath, nextContent)
    }
  }
}

const collectStaticAssetReferences = (packageRoot) => {
  const staticRoot = path.join(packageRoot, staticRootName)
  const assets = walkFiles(staticRoot)
    .filter(isAssetFile)
    .map((filePath) => toPosix(path.relative(packageRoot, filePath)))

  const refs = {
    main: new Set(),
    subpackage: new Set()
  }

  const textFiles = walkFiles(packageRoot).filter(isTextFile)
  for (const filePath of textFiles) {
    const relFile = toPosix(path.relative(packageRoot, filePath))
    const scope = relFile.startsWith(`${subpackageRootName}/`) ? refs.subpackage : refs.main
    const content = fs.readFileSync(filePath, 'utf8')

    for (const asset of assets) {
      if (content.includes(asset)) {
        scope.add(asset)
      }
    }
  }

  return refs
}

const relocateSubpackageStaticAssets = (packageRoot) => {
  const refs = collectStaticAssetReferences(packageRoot)
  const subpackageOnlyAssets = [...refs.subpackage].filter((asset) => !refs.main.has(asset))
  const subpackageStaticRoot = path.join(packageRoot, subpackageRootName, staticRootName)

  for (const asset of subpackageOnlyAssets) {
    const assetSubPath = asset.slice(`${staticRootName}/`.length)
    const src = path.join(packageRoot, asset)
    const dest = path.join(subpackageStaticRoot, assetSubPath)
    if (!fs.existsSync(src)) {
      continue
    }

    ensureDir(path.dirname(dest))
    fs.copyFileSync(src, dest)
  }

  const subpackageDir = path.join(packageRoot, subpackageRootName)
  for (const filePath of walkFiles(subpackageDir).filter(isTextFile)) {
    const content = fs.readFileSync(filePath, 'utf8')
    let nextContent = content
    for (const asset of subpackageOnlyAssets) {
      const assetSubPath = asset.slice(`${staticRootName}/`.length)
      nextContent = rewriteStaticAssetReference(
        nextContent,
        assetSubPath,
        `/${subpackageRootName}/${staticRootName}/${assetSubPath}`
      )
    }
    if (nextContent !== content) {
      fs.writeFileSync(filePath, nextContent)
    }
  }

  const staticRoot = path.join(packageRoot, staticRootName)
  for (const filePath of walkFiles(staticRoot).filter(isAssetFile)) {
    const asset = toPosix(path.relative(packageRoot, filePath))
    if (!refs.main.has(asset)) {
      fs.rmSync(filePath, { force: true })
    }
  }

  removeEmptyDirectories(staticRoot)
}

const getMagickBinary = () => {
  const binary = process.env.MAGICK_BIN || 'magick'
  try {
    childProcess.execFileSync(binary, ['-version'], { stdio: 'ignore' })
    return binary
  } catch (error) {
    console.warn(`[prepare-wechat-project] ImageMagick not found, skip image optimization: ${binary}`)
    return null
  }
}

const replaceWithSmallerImage = (source, args, magickBinary) => {
  if (!fs.existsSync(source)) {
    return false
  }

  const tmp = `${source}.${process.pid}.tmp${path.extname(source)}`
  fs.rmSync(tmp, { force: true })

  try {
    childProcess.execFileSync(magickBinary, [source, ...args, tmp], { stdio: 'ignore' })
    if (fs.existsSync(tmp) && fs.statSync(tmp).size < fs.statSync(source).size) {
      fs.renameSync(tmp, source)
      return true
    }
  } catch (error) {
    console.warn(`[prepare-wechat-project] Failed to optimize image: ${source}`)
  }

  fs.rmSync(tmp, { force: true })
  return false
}

const convertLoginBackgroundToJpeg = (packageRoot, magickBinary) => {
  const pngAsset = `${staticRootName}/image/login-bg.png`
  const jpgAsset = `${staticRootName}/image/login-bg.jpg`
  const pngPath = path.join(packageRoot, pngAsset)
  const jpgPath = path.join(packageRoot, jpgAsset)

  if (!fs.existsSync(pngPath)) {
    return
  }

  fs.rmSync(jpgPath, { force: true })
  try {
    childProcess.execFileSync(magickBinary, [pngPath, '-strip', '-quality', jpegQuality, jpgPath], {
      stdio: 'ignore'
    })

    if (fs.existsSync(jpgPath) && fs.statSync(jpgPath).size < fs.statSync(pngPath).size) {
      replaceAssetReferences(packageRoot, pngAsset, jpgAsset)
      fs.rmSync(pngPath, { force: true })
    } else {
      fs.rmSync(jpgPath, { force: true })
    }
  } catch (error) {
    fs.rmSync(jpgPath, { force: true })
    console.warn('[prepare-wechat-project] Failed to convert login background to JPEG')
  }
}

const optimizePackageImages = (packageRoot) => {
  const magickBinary = getMagickBinary()
  if (!magickBinary) {
    return
  }

  convertLoginBackgroundToJpeg(packageRoot, magickBinary)

  const imageRoots = [
    path.join(packageRoot, staticRootName),
    path.join(packageRoot, subpackageRootName, staticRootName)
  ]

  for (const imageRoot of imageRoots) {
    for (const filePath of walkFiles(imageRoot)) {
      const ext = path.extname(filePath).toLowerCase()
      if (ext === '.png') {
        replaceWithSmallerImage(
          filePath,
          ['-strip', '-colors', pngColorLimit, '-define', 'png:compression-level=9'],
          magickBinary
        )
      } else if (ext === '.jpg' || ext === '.jpeg') {
        replaceWithSmallerImage(filePath, ['-strip', '-quality', jpegQuality], magickBinary)
      }
    }
  }
}

const preparePackageAssets = (packageRoot) => {
  relocateSubpackageStaticAssets(packageRoot)
  optimizePackageImages(packageRoot)
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

preparePackageAssets(distRoot)
copyDir(distRoot, previewRoot)
