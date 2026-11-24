
/*  Neste aqruivo: Acões gerais: menu, voltar ao topo, tema, comportamentos em comum. 
    Aplica-se a todas as páginas ".html".
*/

document.addEventListener("DOMContentLoaded", function () {
    const inputCpf = document.getElementById("cpf");
    const inputTelefone = document.getElementById("telefone");
    const formCadastro = document.getElementById("form-cadastro");
    const inputSenha = document.getElementById("senha");
    const inputConfirmarSenha = document.getElementById("confirmar_senha");
    const erroSenha = document.getElementById("erro-senha");

    // 1. Máscara de CPF: 111.111.111-11
    if (inputCpf) {
        inputCpf.addEventListener("input", function (e) {
            let valor = e.target.value.replace(/\D/g, ""); // Remove tudo que não é dígito
            if (valor.length > 11) valor = valor.slice(0, 11);

            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");

            e.target.value = valor;
        });
    }

    // 2. Máscara de Telefone: (99) 99999-9999
    if (inputTelefone) {
        inputTelefone.addEventListener("input", function (e) {
            let valor = e.target.value.replace(/\D/g, ""); // Remove tudo que não é dígito
            if (valor.length > 11) valor = valor.slice(0, 11);

            valor = valor.replace(/^(\d{2})(\d)/g, "($1) $2");
            valor = valor.replace(/(\d)(\d{4})$/, "$1-$2");

            e.target.value = valor;
        });
    }

    // 3. Validação de Confirmação de Senha no envio do formulário
    if (formCadastro && inputSenha && inputConfirmarSenha) {
        formCadastro.addEventListener("submit", function (e) {
            if (inputSenha.value !== inputConfirmarSenha.value) {
                e.preventDefault(); // Impede o envio do formulário
                erroSenha.style.display = "block";
                inputConfirmarSenha.focus();
            } else {
                erroSenha.style.display = "none";
            }
        });

        // Oculta o erro assim que o usuário corrigir a digitação
        inputConfirmarSenha.addEventListener("input", function () {
            if (inputSenha.value === inputConfirmarSenha.value) {
                erroSenha.style.display = "none";
            }
        });
    }
});


