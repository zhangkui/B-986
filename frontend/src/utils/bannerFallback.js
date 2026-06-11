const SCENE_META = {
  home: {
    title: '不良反应填报平台',
    subtitle: '药品 / 医疗器械 / 化妆品',
    from: '#1d4ed8',
    to: '#10b981'
  },
  level2: {
    title: '分类知识',
    subtitle: '报告前请先了解',
    from: '#0369a1',
    to: '#0ea5e9'
  },
  level2_cat1: {
    title: '药品安全',
    subtitle: '不良反应报告',
    from: '#0f766e',
    to: '#14b8a6'
  },
  level2_cat2: {
    title: '医疗器械安全',
    subtitle: '不良事件报告',
    from: '#4338ca',
    to: '#6366f1'
  },
  level2_cat3: {
    title: '化妆品安全',
    subtitle: '不良反应报告',
    from: '#be185d',
    to: '#ec4899'
  },
  level3_cat1: {
    title: '药品填报须知',
    subtitle: '请确认关键信息',
    from: '#0f766e',
    to: '#10b981'
  },
  level3_cat2: {
    title: '器械填报须知',
    subtitle: '请确认关键信息',
    from: '#4338ca',
    to: '#8b5cf6'
  },
  level3_cat3: {
    title: '化妆品填报须知',
    subtitle: '请确认关键信息',
    from: '#be123c',
    to: '#f43f5e'
  },
  default: {
    title: '填报平台',
    subtitle: '默认横幅',
    from: '#1f2937',
    to: '#4b5563'
  }
}

const cache = new Map()

function buildSvg(meta) {
  const title = meta.title || '填报平台'
  const subtitle = meta.subtitle || '默认横幅'
  const from = meta.from || '#1f2937'
  const to = meta.to || '#4b5563'

  return `
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="360" viewBox="0 0 1200 360" role="img" aria-label="${title}">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${from}" />
      <stop offset="100%" stop-color="${to}" />
    </linearGradient>
  </defs>
  <rect width="1200" height="360" fill="url(#g)" />
  <circle cx="1040" cy="70" r="110" fill="rgba(255,255,255,0.22)" />
  <circle cx="960" cy="300" r="180" fill="rgba(255,255,255,0.12)" />
  <g stroke="rgba(255,255,255,0.18)" stroke-width="2">
    <line x1="0" y1="60" x2="1200" y2="20" />
    <line x1="0" y1="110" x2="1200" y2="70" />
    <line x1="0" y1="160" x2="1200" y2="120" />
    <line x1="0" y1="210" x2="1200" y2="170" />
  </g>
  <text x="56" y="158" fill="#fff" font-size="42" font-family="Microsoft YaHei, PingFang SC, Segoe UI, Arial, sans-serif" font-weight="700">${title}</text>
  <text x="56" y="206" fill="#fff" font-size="24" font-family="Microsoft YaHei, PingFang SC, Segoe UI, Arial, sans-serif">${subtitle}</text>
</svg>`.trim()
}

export function getBannerFallbackDataUri(scene = 'default') {
  const key = Object.prototype.hasOwnProperty.call(SCENE_META, scene) ? scene : 'default'
  if (cache.has(key)) {
    return cache.get(key)
  }
  const svg = buildSvg(SCENE_META[key])
  const uri = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`
  cache.set(key, uri)
  return uri
}
