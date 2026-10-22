/*
 * Menu lateral dos painéis (12/10/2026): o SÍMBOLO do PointMed é o botão.
 *
 *  - Computador (1024 px ou mais): clicar no símbolo recolhe o menu para
 *    uma faixa estreita (só o símbolo e os ícones) e clicar de novo abre
 *    (símbolo, nome "PointMed" e os nomes dos itens). A escolha fica
 *    guardada neste navegador (localStorage) e vale nas próximas páginas.
 *  - Celular/tablet: o símbolo fica no topo, do lado esquerdo, no lugar do
 *    antigo ☰. Clicar abre a gaveta; clicar no símbolo da gaveta fecha.
 *
 * Carregado no <head> SEM "defer", antes do Alpine: assim a classe
 * "fm-menu-recolhido" já está no <html> quando a página desenha, e o menu
 * não "pisca" aberto antes de recolher.
 */
(function () {
    var CHAVE = 'fm-menu-recolhido';
    var COMPUTADOR = '(min-width: 1024px)';

    // O navegador pode bloquear o localStorage (aba anônima, por exemplo):
    // sem ele, o menu só começa aberto.
    function ler() {
        try { return window.localStorage.getItem(CHAVE) === '1'; } catch (e) { return false; }
    }

    function gravar(recolhido) {
        try { window.localStorage.setItem(CHAVE, recolhido ? '1' : '0'); } catch (e) { /* sem memória: tudo bem */ }
    }

    document.documentElement.classList.toggle(CHAVE, ler());

    // Usado em <body x-data="fmMenu()"> (layouts/painel.blade.php).
    window.fmMenu = function () {
        return {
            menu: false,      // gaveta aberta (celular)
            perfil: false,    // menu da conta, no topo
            recolhido: document.documentElement.classList.contains(CHAVE),

            // Clique no símbolo DENTRO do menu lateral.
            alternarMarca: function () {
                if (window.matchMedia(COMPUTADOR).matches) {
                    this.recolhido = !this.recolhido;
                    document.documentElement.classList.toggle(CHAVE, this.recolhido);
                    gravar(this.recolhido);
                } else {
                    this.menu = false;
                }
            },

            // Texto do botão para leitor de tela.
            rotuloMarca: function () {
                if (!window.matchMedia(COMPUTADOR).matches) { return 'Fechar menu'; }
                return this.recolhido ? 'Abrir menu' : 'Recolher menu';
            }
        };
    };
})();
