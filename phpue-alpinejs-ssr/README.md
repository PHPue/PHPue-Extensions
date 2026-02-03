# PHPue's Maintained AlpineJS (SSR Ready)
PHPue's AlpineJS extension focuses on addressing three main issues:

- 1.1: https://github.com/alpinejs/alpine/issues/196
    - 1.2: Bind x-for items to server rendered items #196
- 2.1: https://github.com/alpinejs/alpine/issues/329
    - 2.2: x-for - how to use with pre-existing lists in html? #329
- 3.1: https://github.com/alpinejs/alpine/issues/450 
    - 3.2: ([Bug] x-for should generate valid/compliant html when used on elements such as lists, tables, selects etc #450

AlpineJS's original x-for was problematic inside PHPue, so we created x-ssr that takes the existing PHP rendered list, and then hydrates the rendered list! Allowing you to remove, or add to the list without having to refresh the window or create complicated JS hacks!

**x-ssr** works with other AlpineJS attributes like x-show, or x-intercept for infinite scroll patterns! A method you could do, is limit PHP to render the first 10 items for Google, or initial load speeds, then use x-intercept and x-ssr to add more to the list, so the user can scroll down with ease!

There is many other usecases too, and really helps developing time. Unfortunately, x-for doesn't work very well with PHPue, and p-for and x-ssr works very good together for reactivity!

Try out this extension, and check out the examples!<br>
(example-1 is beginner friendly!)

Before trying extensions, make sure you understand how PHPue works first!

**Further Information:** This extension works outside PHPue, if you want it for your Laravel, another PHP framework or raw PHP project!

**How much testing have I conducted?**<br>
Enough to get it working consistently! It may not be completely bug proof, so anyone wants to work on this, feel free to fork!