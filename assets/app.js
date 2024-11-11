/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/bespoke.css';
import bespoke from "bespoke";
import touch from "bespoke-touch";
import hash from "bespoke-hash";
import classes from "bespoke-classes";
import bullets from "bespoke-bullets";
import 'preline';

window.addEventListener('load', () => {

    handlePresentation();
    registerMenuToggles();


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

    const allSubTitles = document.querySelectorAll(".treemenu");
    const inMemory = JSON.parse(localStorage.getItem("menu-settings") ?? "{}");
    allSubTitles.forEach((el) => {
        if (inMemory[el.dataset.path]) {
            el.parentElement.setAttribute("open", 1);
        }
        el.addEventListener("click", () => {
            inMemory[el.dataset.path] = !el.parentElement.hasAttribute("open");
            localStorage.setItem("menu-settings", JSON.stringify(inMemory))
        })

    })
}

function goNext(deck) {
    deck.next();
}

function goPrevious(deck) {
    deck.prev();
}

function startPresentation() {
    const presentationContainer = document.querySelector("#presentation");
    presentationContainer.classList.remove("hidden");
    const keyBind = function () {
        return function (deck) {
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    deck.slide(0);
                    presentationContainer.classList.add("hidden");
                }
                if (e.key === 'PageDown' || // PAGE DOWN
                    (e.key === ' ' && !e.shiftKey) || // SPACE WITHOUT SHIFT
                    (e.key === 'ArrowRight') // RIGHT
                ) {
                    goNext(deck);
                }

                if (e.key === 'PageUp' || // PAGE UP
                    (e.key === ' ' && e.shiftKey) || // SPACE + SHIFT
                    (e.key === 'ArrowLeft')// UP
                ) {
                    goPrevious(deck);
                }
            });
        }
    }
    var deck = bespoke.from({parent: presentationContainer}, [bullets('.subslide, div:not(.subslide) li'), keyBind(), hash(), classes(), touch(),]);
}


function registerMenuToggles() {

    const toggleMainMenuBtn = document.querySelector("#toggle-main-menu");
    if (!toggleMainMenuBtn) {
        return;
    }
    const toggleContextualMenuBtn = document.querySelector("#toggle-contextual-menu");
    refreshActiveToc();

    let hasManuallyOpenedMenu = false;
    toggleMainMenuBtn.addEventListener("click", () => {
        setTimeout(() => {
            hasManuallyOpenedMenu = false
        }, 200);
        document.querySelector('.main-menu').classList.toggle('main-menu-normal');
        document.querySelector('#files-container').classList.toggle('hidden')
    });
    toggleContextualMenuBtn.addEventListener("click", () => {
        hasManuallyOpenedMenu = true;
        document.querySelector('.context-menu').classList.toggle('context-menu-normal');
        document.querySelectorAll('.context-menu>:not(.heading-title)').forEach(item => item.classList.toggle('hidden'))
        setTimeout(() => {
            hasManuallyOpenedMenu = false
        }, 200);
    })


    window.addEventListener("scroll", () => {
        if (hasManuallyOpenedMenu) {
            return;
        }
        document.querySelector('.main-menu').classList.remove('main-menu-normal');
        document.querySelector('#files-container').classList.add('hidden')

        document.querySelectorAll('.context-menu>:not(.heading-title)').forEach(item => item.classList.add('hidden'))
        document.querySelector('.context-menu').classList.remove('context-menu-normal');


    })
    window.addEventListener("hashchange", () => {
        refreshActiveToc()
    }, false,);
}

function handlePresentation() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('slides')) {
        startPresentation();
    }
    const presentationBtn = document.querySelector("#start-presentation-button");
    if (!presentationBtn) {
        return;
    }
    presentationBtn.addEventListener("click", () => {
        startPresentation();
    })

}