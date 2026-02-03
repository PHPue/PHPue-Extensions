// Author: Edward Patch
(function() {
    document.addEventListener('alpine:init', () => {
        // Use a global variable if needed (PHP output can define it in <cscript>)
        var initialNotifications = window.notifications || [];

        // csrTemplate is used to tell x-ssr what elements to add, when array is populated
        // ${item.id} and ${item.content} is what $notifications had, you can add more if your item has more attributes!
        // You can add a list of clickable items like buttons inside `process:`, for example, I added
        // the dismiss function! (x-for used template, but x-ssr uses DOM from below)
        // DX rating? 8/10 
        // Note: (x-for doesn't work on PHPue at all because template clash!)(nested templates and REGEX)

        // I personally like x-ssr because it can work with x-intercept for infinite scrolls, whilst,
        // giving Google specialised queries for SEO! OR use PHP to generate a search, and then x-ssr,
        // to change query on user type, and give user reactivity on type! (without x-ssr, 
        // Alpine's x-for would be useless in PHPue! (we can now use p-for (PHP FOR) and x-ssr together))
        Alpine.data('notificationHandler', () => ({
            isOpen: false,
            notifications: initialNotifications,
            csrTemplate: {
                html: `
                    <div data-id="\${item.id}" class="notification-item flex items-center justify-between py-2 border-b last:border-0">
                        <span>\${item.content}</span>
                        <button class="text-red-500 text-sm hover:underline">Dismiss</button>
                    </div>
                `,
                process: function(element, item, component) {
                    const button = element.querySelector('button');
                    button.onclick = (e) => {
                        e.preventDefault();
                        if (component.dismiss) component.dismiss(item.id);
                    };
                }
            },

            init() {
                console.log('🔔 Notification handler initialized', this.notifications);
            },

            get notificationCount() {
                return this.notifications.length;
            },

            dismiss(id) {
                const original = [...this.notifications];
                this.notifications = this.notifications.filter(n => n.id != id);

                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'dismissNotification', id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success') this.notifications = original;
                })
                .catch(() => { this.notifications = original; });
            },

            reset() {
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'resetNotifications' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') this.notifications = data.notifications;
                });
            }
        }));
    });
})();