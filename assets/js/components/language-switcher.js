/**
 * Language Switcher Component
 * Handles dropdown toggle and keyboard navigation
 */

class LanguageSwitcher {
    constructor(element) {
        this.element = element;
        this.toggle = element.querySelector('.language-switcher__toggle');
        this.menu = element.querySelector('.language-switcher__menu');
        this.links = element.querySelectorAll('.language-switcher__link:not(.language-switcher__link--unavailable)');
        
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        // Toggle on click
        this.toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMenu();
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target)) {
                this.closeMenu();
            }
        });
        
        // Keyboard navigation
        this.toggle.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.openMenu();
                this.focusFirstLink();
            }
        });
        
        this.menu.addEventListener('keydown', (e) => {
            this.handleMenuKeydown(e);
        });
        
        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
                this.toggle.focus();
            }
        });
    }
    
    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }
    
    openMenu() {
        this.isOpen = true;
        this.toggle.setAttribute('aria-expanded', 'true');
    }
    
    closeMenu() {
        this.isOpen = false;
        this.toggle.setAttribute('aria-expanded', 'false');
    }
    
    focusFirstLink() {
        if (this.links.length > 0) {
            this.links[0].focus();
        }
    }
    
    handleMenuKeydown(e) {
        const currentIndex = Array.from(this.links).indexOf(document.activeElement);
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.focusNextLink(currentIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.focusPreviousLink(currentIndex);
                break;
            case 'Home':
                e.preventDefault();
                this.links[0].focus();
                break;
            case 'End':
                e.preventDefault();
                this.links[this.links.length - 1].focus();
                break;
            case 'Tab':
                if (!e.shiftKey && currentIndex === this.links.length - 1) {
                    this.closeMenu();
                } else if (e.shiftKey && currentIndex === 0) {
                    this.closeMenu();
                }
                break;
        }
    }
    
    focusNextLink(currentIndex) {
        const nextIndex = (currentIndex + 1) % this.links.length;
        this.links[nextIndex].focus();
    }
    
    focusPreviousLink(currentIndex) {
        const prevIndex = currentIndex <= 0 ? this.links.length - 1 : currentIndex - 1;
        this.links[prevIndex].focus();
    }
}

// Initialize all language switchers
document.addEventListener('DOMContentLoaded', () => {
    const switchers = document.querySelectorAll('.language-switcher');
    switchers.forEach(switcher => new LanguageSwitcher(switcher));
});

export default LanguageSwitcher;
