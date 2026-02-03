// alpine.ext.ssr.js
// Author: Edward Patch
// Description: Alpine.js extension for Server-Side Rendering (SSR) support (x-for)
// Website: https://phpue.co.uk/ue-extensions
(function() {
    'use strict';

    const escapeHTML = str =>
        String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    
    document.addEventListener('alpine:init', function() {        
        Alpine.directive('ssr', (el, { expression }, { Alpine, effect, evaluate }) => {            
            const component = Alpine.closestDataStack(el);
            if (!component) {
                console.error('x-ssr: No Alpine component found');
                return;
            }
            
            let actualComponent = component;
            if (Array.isArray(component) && component.length > 0) {
                actualComponent = component[component.length - 1];
            }
            
            const itemKey = el.getAttribute('x-ssr-key') || 'id';
            const templateName = el.getAttribute('x-ssr-template') || 'csrTemplate';
                        
            const serverItems = [];
            const childDivs = el.querySelectorAll(':scope > div');
            
            childDivs.forEach(childDiv => {
                const items = childDiv.querySelectorAll(`[data-${itemKey}]`);
                items.forEach(item => serverItems.push(item));
            });
            
            const serverItemsMap = new Map();
            serverItems.forEach(item => {
                const key = item.getAttribute(`data-${itemKey}`);
                if (key) serverItemsMap.set(key, { element: item, key });
            });
                        
            const domItems = new Map(serverItemsMap);
            
            function getItemKey(dataItem) {
                return typeof dataItem === 'object' ? String(dataItem[itemKey]) : String(dataItem);
            }
            
            function getCSRTemplate(comp, templateName) {                
                let template = null;
                
                if (comp[templateName] !== undefined) {
                    template = comp[templateName];
                }
                
                if (!template && comp.$data && comp.$data[templateName]) {
                    template = comp.$data[templateName];
                }
                
                if (!template) {
                    try {
                        template = evaluate(templateName);
                    } catch (e) {
                        console.warn(`⚠️ Could not evaluate '${templateName}':`, e.message);
                    }
                }
                
                if (!template) {
                    console.error(`❌ No template found: ${templateName}`);
                    return null;
                }
                
                if (typeof template === 'object' && template.html) {
                    return template;
                }
                if (typeof template === 'string') {
                    return { html: template };
                }
                
                return null;
            }
            
            function createCSRItem(dataItem) {
                const key = getItemKey(dataItem);

                const template = getCSRTemplate(actualComponent, templateName);
                
                if (!template) {
                    console.error('x-ssr: No CSR template available');
                    return null;
                }
                
                let html = template.html;
                
                if (typeof dataItem === 'object') {
                    Object.keys(dataItem).forEach(prop => {
                        const placeholder = `\${item.${prop}}`;
                        const value = escapeHTML(dataItem[prop]);
                        while (html.includes(placeholder)) {
                            html = html.replace(placeholder, value);
                        }
                    });
                }
                
                const temp = document.createElement('div');
                temp.innerHTML = html.trim();
                const newElement = temp.firstChild;
                
                if (!newElement) {
                    console.error('x-ssr: Failed to create element');
                    return null;
                }
                
                newElement.setAttribute(`data-${itemKey}`, key);
                
                if (typeof template.process === 'function') {
                    try {
                        template.process(newElement, dataItem, actualComponent);
                    } catch (e) {
                        console.error('x-ssr: Error in template.process:', e);
                    }
                }
                
                return newElement;
            }
            
            effect(() => {
                try {
                    const dataItems = evaluate(expression);
                    
                    if (!Array.isArray(dataItems)) {
                        console.error('x-ssr: Expression must evaluate to an array');
                        return;
                    }
                                        
                    const dataItemsMap = new Map();
                    dataItems.forEach(item => {
                        dataItemsMap.set(getItemKey(item), item);
                    });
                    
                    domItems.forEach((domInfo, key) => {
                        if (!dataItemsMap.has(key)) {
                            domInfo.element.remove();
                            domItems.delete(key);
                        }
                    });
                    
                    dataItemsMap.forEach((dataItem, key) => {
                        if (!domItems.has(key)) {                            
                            const serverInfo = serverItemsMap.get(key);
                            if (serverInfo) {
                                el.appendChild(serverInfo.element);
                                domItems.set(key, serverInfo);
                            } else {
                                const newElement = createCSRItem(dataItem);
                                if (newElement) {
                                    el.appendChild(newElement);
                                    domItems.set(key, { element: newElement, key, isCSR: true });
                                }
                            }
                        }
                    });
                    
                } catch (error) {
                    console.error('x-ssr: Error processing data', error);
                }
            });
        });
    });
    
    if (window.Alpine) {
        document.dispatchEvent(new CustomEvent('alpine:init'));
    }
})();