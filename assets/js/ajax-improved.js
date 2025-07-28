/**
 * AjaxManager - Sistema AJAX melhorado com cache, retry e error handling
 * Substitui ajax-utils.js com melhor arquitetura
 */

class AjaxManager {
    constructor(options = {}) {
        this.baseURL = options.baseURL || '';
        this.timeout = options.timeout || 30000;
        this.retryAttempts = options.retryAttempts || 3;
        this.retryDelay = options.retryDelay || 1000;
        this.cache = new Map();
        this.activeRequests = new Map();
        this.requestId = 0;
        
        // Configurações padrão
        this.defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: false,
            retries: true
        };
        
        this.init();
    }

    /**
     * Inicialização
     */
    init() {
        this.setupGlobalHandlers();
        this.setupNetworkMonitoring();
    }

    /**
     * Configuração de handlers globais
     */
    setupGlobalHandlers() {
        // Intercepta erros de rede globais
        window.addEventListener('online', this.handleOnline.bind(this));
        window.addEventListener('offline', this.handleOffline.bind(this));
        
        // Intercepta navegação para cancelar requests
        window.addEventListener('beforeunload', this.cancelAllRequests.bind(this));
    }

    /**
     * Monitoramento de rede
     */
    setupNetworkMonitoring() {
        this.isOnline = navigator.onLine;
        this.networkSpeed = this.detectNetworkSpeed();
    }

    /**
     * Request principal
     * @param {string} url 
     * @param {Object} options 
     * @returns {Promise}
     */
    async request(url, options = {}) {
        const config = this.mergeConfig(options);
        const requestKey = this.generateRequestKey(url, config);
        
        // Verifica cache
        if (config.cache && this.cache.has(requestKey)) {
            const cached = this.cache.get(requestKey);
            if (!this.isCacheExpired(cached)) {
                return Promise.resolve(cached.data);
            }
        }
        
        // Verifica se já existe request em andamento
        if (this.activeRequests.has(requestKey)) {
            return this.activeRequests.get(requestKey);
        }
        
        const requestPromise = this.executeRequest(url, config, requestKey);
        this.activeRequests.set(requestKey, requestPromise);
        
        try {
            const result = await requestPromise;
            return result;
        } finally {
            this.activeRequests.delete(requestKey);
        }
    }

    /**
     * Executa request com retry
     * @param {string} url 
     * @param {Object} config 
     * @param {string} requestKey 
     * @returns {Promise}
     */
    async executeRequest(url, config, requestKey) {
        let lastError;
        const maxAttempts = config.retries ? this.retryAttempts : 1;
        
        for (let attempt = 1; attempt <= maxAttempts; attempt++) {
            try {
                const result = await this.performRequest(url, config);
                
                // Cache do resultado se configurado
                if (config.cache) {
                    this.cacheResult(requestKey, result);
                }
                
                return result;
                
            } catch (error) {
                lastError = error;
                
                // Não faz retry em casos específicos
                if (!this.shouldRetry(error, attempt, maxAttempts)) {
                    break;
                }
                
                // Delay antes do retry
                if (attempt < maxAttempts) {
                    await this.delay(this.retryDelay * attempt);
                }
            }
        }
        
        throw this.enhanceError(lastError, url, config);
    }

    /**
     * Executa request real
     * @param {string} url 
     * @param {Object} config 
     * @returns {Promise}
     */
    async performRequest(url, config) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        
        try {
            const response = await fetch(this.buildURL(url), {
                ...config,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await this.parseResponse(response, config.responseType);
            
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    /**
     * Métodos de conveniência
     */
    async get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    }

    async post(url, data, options = {}) {
        return this.request(url, {
            ...options,
            method: 'POST',
            body: this.prepareBody(data, options.headers)
        });
    }

    async put(url, data, options = {}) {
        return this.request(url, {
            ...options,
            method: 'PUT',
            body: this.prepareBody(data, options.headers)
        });
    }

    async patch(url, data, options = {}) {
        return this.request(url, {
            ...options,
            method: 'PATCH',
            body: this.prepareBody(data, options.headers)
        });
    }

    async delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    }

    /**
     * Upload de arquivos
     * @param {string} url 
     * @param {FormData} formData 
     * @param {Object} options 
     * @returns {Promise}
     */
    async upload(url, formData, options = {}) {
        const config = {
            ...options,
            method: 'POST',
            body: formData,
            headers: {
                ...options.headers
                // Não define Content-Type para FormData (browser define automaticamente)
            }
        };
        
        // Remove Content-Type para uploads
        if (config.headers['Content-Type']) {
            delete config.headers['Content-Type'];
        }
        
        return this.request(url, config);
    }

    /**
     * Request paralelos
     * @param {Array} requests 
     * @returns {Promise}
     */
    async parallel(requests) {
        const promises = requests.map(req => {
            if (typeof req === 'string') {
                return this.get(req);
            }
            return this.request(req.url, req.options);
        });
        
        return Promise.allSettled(promises);
    }

    /**
     * Cache management
     */
    clearCache(pattern = null) {
        if (pattern) {
            const regex = new RegExp(pattern);
            for (const [key] of this.cache) {
                if (regex.test(key)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    /**
     * Cancela todas as requests ativas
     */
    cancelAllRequests() {
        this.activeRequests.clear();
    }

    /**
     * Helpers
     */
    mergeConfig(options) {
        return {
            ...this.defaults,
            ...options,
            headers: {
                ...this.defaults.headers,
                ...options.headers
            }
        };
    }

    generateRequestKey(url, config) {
        const keyData = {
            url: this.buildURL(url),
            method: config.method,
            body: config.body,
            headers: config.headers
        };
        return btoa(JSON.stringify(keyData));
    }

    buildURL(url) {
        if (url.startsWith('http')) return url;
        return `${this.baseURL}${url}`;
    }

    prepareBody(data, headers = {}) {
        if (data instanceof FormData) return data;
        
        const contentType = headers['Content-Type'] || this.defaults.headers['Content-Type'];
        
        if (contentType.includes('application/json')) {
            return JSON.stringify(data);
        }
        
        if (contentType.includes('application/x-www-form-urlencoded')) {
            return new URLSearchParams(data).toString();
        }
        
        return data;
    }

    async parseResponse(response, responseType = 'json') {
        const contentType = response.headers.get('content-type') || '';
        
        if (responseType === 'text' || contentType.includes('text/')) {
            return response.text();
        }
        
        if (responseType === 'blob') {
            return response.blob();
        }
        
        if (responseType === 'arrayBuffer') {
            return response.arrayBuffer();
        }
        
        if (contentType.includes('application/json')) {
            return response.json();
        }
        
        return response.text();
    }

    cacheResult(key, data) {
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            ttl: 5 * 60 * 1000 // 5 minutos
        });
    }

    isCacheExpired(cached) {
        return Date.now() - cached.timestamp > cached.ttl;
    }

    shouldRetry(error, attempt, maxAttempts) {
        if (attempt >= maxAttempts) return false;
        if (!this.isOnline) return false;
        
        // Não retry em erros 4xx (client errors)
        if (error.message.includes('HTTP 4')) return false;
        
        return true;
    }

    enhanceError(error, url, config) {
        const enhanced = new Error(error.message);
        enhanced.url = url;
        enhanced.config = config;
        enhanced.timestamp = new Date().toISOString();
        enhanced.networkStatus = this.isOnline ? 'online' : 'offline';
        enhanced.originalError = error;
        return enhanced;
    }

    async delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    detectNetworkSpeed() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return connection ? connection.effectiveType : 'unknown';
    }

    handleOnline() {
        this.isOnline = true;
        this.emit('network-online');
    }

    handleOffline() {
        this.isOnline = false;
        this.emit('network-offline');
    }

    /**
     * Event system simples
     */
    emit(event, data = null) {
        window.dispatchEvent(new CustomEvent(`ajax-${event}`, { 
            detail: data 
        }));
    }
}

/**
 * ContentLoader - Carregador de conteúdo específico para o sistema
 */
class ContentLoader {
    constructor(ajaxManager) {
        this.ajax = ajaxManager;
        this.loadingElements = new Set();
    }

    /**
     * Carrega conteúdo em um container
     * @param {string} url 
     * @param {HTMLElement|string} container 
     * @param {Object} options 
     */
    async loadContent(url, container, options = {}) {
        const element = typeof container === 'string' ? 
            document.querySelector(container) : container;
            
        if (!element) {
            throw new Error('Container não encontrado');
        }

        try {
            this.showLoading(element, options.loadingType);
            
            const content = await this.ajax.get(url, {
                responseType: 'text',
                cache: options.cache !== false
            });
            
            this.updateContent(element, content, options);
            this.emit('content-loaded', { url, element, content });
            
        } catch (error) {
            this.showError(element, error, options);
            this.emit('content-error', { url, element, error });
        } finally {
            this.hideLoading(element);
        }
    }

    /**
     * Carrega conteúdo de submenu
     * @param {string} menuId 
     * @param {string} url 
     */
    async loadSubmenu(menuId, url) {
        const container = document.querySelector('.main-content-inner');
        if (!container) return;

        await this.loadContent(url, container, {
            loadingType: 'skeleton',
            cache: true,
            fadeIn: true
        });
        
        // Atualiza estado do menu
        this.updateActiveMenu(menuId);
    }

    /**
     * Mostra estado de loading
     */
    showLoading(element, type = 'spinner') {
        this.loadingElements.add(element);
        element.classList.add('loading');

        if (type === 'skeleton') {
            this.showSkeleton(element);
        } else {
            this.showSpinner(element);
        }
    }

    showSpinner(element) {
        const spinner = document.createElement('div');
        spinner.className = 'ajax-spinner';
        spinner.innerHTML = `
            <div class="spinner">
                <div class="spinner-inner"></div>
            </div>
            <p>Carregando...</p>
        `;
        
        element.appendChild(spinner);
    }

    showSkeleton(element) {
        const skeleton = document.createElement('div');
        skeleton.className = 'ajax-skeleton';
        skeleton.innerHTML = `
            <div class="skeleton-header"></div>
            <div class="skeleton-content">
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
            </div>
        `;
        
        element.appendChild(skeleton);
    }

    /**
     * Esconde loading
     */
    hideLoading(element) {
        this.loadingElements.delete(element);
        element.classList.remove('loading');
        
        const spinner = element.querySelector('.ajax-spinner');
        const skeleton = element.querySelector('.ajax-skeleton');
        
        if (spinner) spinner.remove();
        if (skeleton) skeleton.remove();
    }

    /**
     * Atualiza conteúdo
     */
    updateContent(element, content, options = {}) {
        if (options.fadeIn) {
            element.style.opacity = '0';
        }
        
        element.innerHTML = content;
        
        if (options.fadeIn) {
            element.style.transition = 'opacity 0.3s';
            element.style.opacity = '1';
        }
        
        // Reexecuta scripts se necessário
        if (options.executeScripts !== false) {
            this.executeScripts(element);
        }
    }

    /**
     * Mostra erro
     */
    showError(element, error, options = {}) {
        const errorHtml = `
            <div class="ajax-error">
                <div class="error-icon">⚠️</div>
                <h3>Erro ao carregar conteúdo</h3>
                <p>${error.message}</p>
                <button onclick="location.reload()" class="retry-btn">
                    Tentar novamente
                </button>
            </div>
        `;
        
        element.innerHTML = errorHtml;
    }

    /**
     * Executa scripts em conteúdo carregado
     */
    executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            newScript.textContent = script.textContent;
            script.replaceWith(newScript);
        });
    }

    updateActiveMenu(menuId) {
        // Remove active de todos os menus
        document.querySelectorAll('.topbar-submenu-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Ativa menu atual
        const activeMenu = document.querySelector(`[data-menu="${menuId}"]`);
        if (activeMenu) {
            activeMenu.classList.add('active');
        }
    }

    emit(event, data) {
        window.dispatchEvent(new CustomEvent(`content-${event}`, { 
            detail: data 
        }));
    }
}

// Inicialização global
const ajaxManager = new AjaxManager({
    baseURL: '/Apps/',
    timeout: 30000,
    retryAttempts: 3
});

const contentLoader = new ContentLoader(ajaxManager);

// Compatibilidade com código legado
window.Ajax = ajaxManager;
window.ContentLoader = contentLoader;

// Funções de conveniência globais
window.loadContent = (url, container, options) => {
    return contentLoader.loadContent(url, container, options);
};

window.loadSubmenu = (menuId, url) => {
    return contentLoader.loadSubmenu(menuId, url);
};

// Export para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AjaxManager, ContentLoader };
}
