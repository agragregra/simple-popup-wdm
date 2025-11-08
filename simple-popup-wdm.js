document.addEventListener('DOMContentLoaded', function () {
  const popups = simplePopupSettings.popups || []

  popups.forEach(function (popup, index) {
    if (popup.trigger_class) {
      const triggers = document.querySelectorAll('.' + popup.trigger_class)
      triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
          e.preventDefault()
          const popupElement = document.getElementById('simple-popup-' + index)
          if (popupElement && !popupElement.classList.contains('active') && !popupElement.classList.contains('closing')) {

            popupElement.classList.remove('closing')
            void popupElement.offsetWidth

            popupElement.classList.add('active')
            document.body.style.overflow = 'hidden'
          }
        })
      })
    }
  })

  function closePopup(popupElement) {
    if (!popupElement.classList.contains('active') || popupElement.classList.contains('closing')) {
      return
    }

    popupElement.classList.add('closing')

    setTimeout(() => {
      popupElement.classList.remove('active', 'closing')
      document.body.style.overflow = 'auto'
    }, 300)
  }

  document.querySelectorAll('.simple-popup__overlay, .simple-popup__close').forEach(function (element) {
    element.addEventListener('click', function () {
      const popupId = this.getAttribute('data-popup-id')
      const popupElement = document.getElementById('simple-popup-' + popupId)
      if (popupElement) {
        closePopup(popupElement)
      }
    })
  })

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.simple-popup.active').forEach(function (popup) {
        closePopup(popup)
      })
    }
  })
})
