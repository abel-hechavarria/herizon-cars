/** Abel wave reveal, with complete cleanup for timeout, imports and reduced motion. */
export function init(runtime) {
  const wrap = document.querySelector('[data-hc-preloader]');
  let timeline, timer, finished = false;
  const finish = () => {
    if (finished) return;
    finished = true; clearTimeout(timer); timeline?.kill();
    if (wrap) { wrap.hidden = true; wrap.inert = true; wrap.style.removeProperty('transform'); }
    document.body.classList.remove('hc-is-loading'); runtime.finishPreloader();
  };
  const onPreference = () => { if (runtime.reduced.matches) finish(); };
  runtime.reduced.addEventListener('change', onPreference);
  if (!wrap || runtime.reduced.matches || runtime.editor() || runtime.preloaderDone) finish();
  else {
    timer = setTimeout(finish, 3250);
    import('gsap').then(({ gsap }) => {
      if (finished || runtime.preloaderDone) { finish(); return; }
      wrap.hidden = false; wrap.inert = true;
      document.body.classList.add('hc-is-loading');
      const text = wrap.querySelector('[data-hc-preloader-text]');
      const letters = text ? [...text.querySelectorAll('span')] : [];
      const wave = wrap.querySelector('[data-hc-preloader-path]');
      const curved = 'M0 502S175 272 500 272s500 230 500 230V0H0Z';
      const flat = 'M0 0S175 0 500 0s500 0 500 0V0H0Z';
      const targets = letters.length ? letters : text;
      if (targets) gsap.set(targets, { y: 0, opacity: 1, clearProps: 'animation' });
      if (wave) gsap.set(wave, { attr: { d: curved } });
      timeline = gsap.timeline({ onComplete: finish });
      if (targets) {
        timeline.set(targets, { animation: 'none' }, 1.5);
        timeline.to(targets, { y: -100, opacity: 0, duration: .3, stagger: { amount: .1 }, ease: 'power2.in' }, 1.5);
      }
      // Matching path commands allow GSAP core AttrPlugin to flatten the wave.
      // Fixed positions keep the full reveal at 3.1s regardless of text length.
      if (wave) timeline.to(wave, { attr: { d: flat }, duration: 1.1, ease: 'power2.inOut' }, 1.9);
      timeline.to(wrap, { y: '-210vh', duration: 1.2, ease: 'power2.inOut' }, 1.9);
    }).catch(finish);
  }
  return () => { runtime.reduced.removeEventListener('change', onPreference); finish(); };
}
