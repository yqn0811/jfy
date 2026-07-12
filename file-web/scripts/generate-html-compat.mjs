import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const distDir = join(process.cwd(), 'dist')
const source = join(distDir, 'index.html')

const routeDirs = [
  'workbench',
  'quick-send',
  'create-collection-task',
  'delivery-records',
  'space-archive',
  'task-details',
  'share-result',
  'submission-upload',
  'submission-success',
  'placeholder',
]

if (!existsSync(source)) {
  throw new Error('dist/index.html does not exist. Run vite build first.')
}

for (const route of routeDirs) {
  const routeDir = join(distDir, route)
  mkdirSync(routeDir, { recursive: true })
  const html = readFileSync(source, 'utf8').replace(/(src|href)="\.\//g, '$1="../')
  writeFileSync(join(routeDir, 'index.html'), html)
}
