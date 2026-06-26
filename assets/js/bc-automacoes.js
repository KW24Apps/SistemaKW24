/* bc-automacoes.js — vanilla JS functions for Base de Conhecimento inner pages.
   All functions live in the bcAuto namespace to avoid global conflicts.
   Use in onclick attributes as: bcAuto.toggle(this), bcAuto.showPage('id', this, 'key')
*/
var bcAuto = (function () {

    /* toggle(header)
       Accordion: opens the section-body immediately after the clicked header,
       closes all other section-bodies within the same .bc-page-stages container.
       Falls back to .bc-inner-main if no .bc-page-stages ancestor is found.
    */
    function toggle(header) {
        var body      = header.nextElementSibling;
        var isOpen    = body.classList.contains('open');
        var container = header.closest('.bc-page-stages') || header.closest('.bc-inner-main');

        container.querySelectorAll('.section-body').forEach(function (b) {
            b.classList.remove('open');
        });
        container.querySelectorAll('.section-toggle').forEach(function (i) {
            i.classList.remove('open');
        });

        if (!isOpen) {
            body.classList.add('open');
            var tog = header.querySelector('.section-toggle');
            if (tog) tog.classList.add('open');
        }
    }

    /* toggleAside(title)
       Collapses/expands the aside-body immediately after the clicked aside-title.
       Rotates the aside-title-arrow to indicate state.
    */
    function toggleAside(title) {
        var body  = title.nextElementSibling;
        var arrow = title.querySelector('.aside-title-arrow');
        body.classList.toggle('collapsed');
        if (arrow) arrow.classList.toggle('collapsed');
    }

    /* toggleField(header)
       Accordion within an aside-body: opens the aside-item-desc immediately after
       the clicked aside-item-header, closes all others in the same aside-body.
    */
    function toggleField(header) {
        var desc   = header.nextElementSibling;
        var isOpen = desc.classList.contains('open');
        var box    = header.closest('.aside-body');

        box.querySelectorAll('.aside-item-desc').forEach(function (d) {
            d.classList.remove('open');
        });
        box.querySelectorAll('.aside-item-arrow').forEach(function (a) {
            a.classList.remove('open');
        });

        if (!isOpen) {
            desc.classList.add('open');
            var arrow = header.querySelector('.aside-item-arrow');
            if (arrow) arrow.classList.add('open');
        }
    }

    /* showPage(pageId, navItem, storageKey)
       Hides all .bc-page divs within the nearest .bc-inner ancestor,
       shows the one with id === pageId, and updates the active nav item.
       storageKey: optional localStorage key to persist the last active page.
    */
    function showPage(pageId, navItem, storageKey) {
        var wrapper = navItem ? (navItem.closest('.bc-inner') || document) : document;

        wrapper.querySelectorAll('.bc-page').forEach(function (p) {
            p.classList.remove('active');
        });

        var target = wrapper.querySelector('#' + pageId);
        if (target) target.classList.add('active');

        wrapper.querySelectorAll('.bc-sidenav-item').forEach(function (s) {
            s.classList.remove('active');
        });
        if (navItem) navItem.classList.add('active');

        if (storageKey) {
            try { localStorage.setItem(storageKey, pageId); } catch (e) {}
        }
    }

    /* restorePage(storageKey, wrapper)
       Call on DOMContentLoaded to restore last active page from localStorage.
       wrapper: the .bc-inner element (or document as fallback).
    */
    function restorePage(storageKey, wrapper) {
        if (!storageKey) return;
        var saved;
        try { saved = localStorage.getItem(storageKey); } catch (e) {}
        if (!saved) return;
        var w = wrapper || document;
        var target = w.querySelector('#' + saved);
        if (!target) return;
        var navItem = w.querySelector('.bc-sidenav-item[data-page="' + saved + '"]');
        showPage(saved, navItem, null);
    }

    return {
        toggle:      toggle,
        toggleAside: toggleAside,
        toggleField: toggleField,
        showPage:    showPage,
        restorePage: restorePage
    };

}());
