/**
 * Alpine ships inside Livewire's bundle — it is not an npm dependency here, so
 * there is nothing to import. Components register on the `alpine:init` event,
 * which Livewire fires before it starts Alpine.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('commandMenu', (destinations = []) => ({
        open: false,
        query: '',
        activeIndex: 0,

        get results() {
            const query = this.query.trim().toLowerCase()

            if (query === '') {
                return destinations
            }

            return destinations.filter(
                (destination) =>
                    destination.label.toLowerCase().includes(query) ||
                    destination.group.toLowerCase().includes(query),
            )
        },

        show() {
            this.open = true
            this.query = ''
            this.activeIndex = 0

            this.$nextTick(() => this.$refs.input?.focus())
        },

        hide() {
            this.open = false
        },

        move(delta) {
            const count = this.results.length

            if (count === 0) {
                return
            }

            this.activeIndex = (this.activeIndex + delta + count) % count
        },

        go(index = this.activeIndex) {
            const destination = this.results[index]

            if (! destination) {
                return
            }

            this.hide()

            // Stay within the SPA-style navigation the rest of the shell uses.
            if (window.Livewire?.navigate) {
                window.Livewire.navigate(destination.url)
            } else {
                window.location.href = destination.url
            }
        },
    }))
})
