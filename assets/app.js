/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

window.addEventListener('load', () => {
    window.addEventListener(
        "hashchange",
        () => {
            refreshActiveToc()
        },
        false,
    );
    const headings = document.querySelectorAll('.heading-permalink');
});

function refreshActiveToc() {
    const tocHeadings = document.querySelectorAll('.toc-item')

    tocHeadings.forEach(th => {
        if (th.attributes["href"].value === location.hash) {
            th.classList.add("active")
        } else {
            th.classList.remove("active")
        }
    })
}