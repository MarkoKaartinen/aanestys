
// Livewire shows its own English messages for failed requests. Replace the
// common ones with the page's language (the texts sit on the <html> element).
document.addEventListener('livewire:init', () => {
    const messages = document.documentElement.dataset

    Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status === 419) {
                preventDefault()

                if (confirm(messages.messageExpired)) {
                    window.location.reload()
                }
            } else if (response.status === 429) {
                preventDefault()
                alert(messages.messageTooMany)
            }
        })
    })
})
