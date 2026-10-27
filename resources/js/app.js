// The theme is the `dark` class on <html>, chosen from the saved choice
// (light, dark or none = follow the system). The inline script in the layout
// sets it on the first load; Livewire's wire:navigate swaps the whole page and
// drops the class, so it is applied again after every swap.
const applyTheme = () => {
    let theme = null

    try {
        theme = localStorage.getItem('theme')
    } catch (e) {}

    const dark = theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)

    document.documentElement.classList.toggle('dark', dark)
}

window.applyTheme = applyTheme

document.addEventListener('livewire:navigating', (event) => event.detail.onSwap(applyTheme))
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyTheme)


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
