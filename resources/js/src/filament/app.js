import sort from '@alpinejs/sort'
Alpine.plugin(sort)

window.initLottie = async (el, path, options = {}) => {
  if (!el) return

  // This creates a separate Lottie chunk.
  const { default: lottie } = await import('lottie-web')

  const anim = lottie.loadAnimation({
    container: el,
    renderer: 'svg',
    loop: options.loop ?? true,
    autoplay: options.autoplay ?? true,
    path,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
      width: '100%',
      height: '100%',
      viewBoxSize: '200 200 1920 1080'
    }
  })

  return anim
}
