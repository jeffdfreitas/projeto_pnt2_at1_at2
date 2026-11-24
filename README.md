# 🍔 SysBurguer 1.0 — Larica Burguer (Solução Delivery)

> Sistema web full-stack desenvolvido para gestão de pedidos de delivery de hamburgueria artesanal, permitindo que clientes realizem pedidos online e acompanhem o preparo em tempo real, enquanto a equipe administrativa gerencia o cardápio e a fila de produção.

Projeto desenvolvido como parte das atividades práticas da disciplina de **Programação Web / Programação em Novas Tecnologias 2 (PNT2)**, focando em usabilidade, regras de negócio reais e experiência fluida para o usuário.

---

## 🚀 Funcionalidades Principais

### 👤 Módulo Cliente
- **Cadastro e Autenticação:** Cadastro único com validação em JavaScript (máscaras de CPF e telefone + verificação de confirmação de senha) e login seguro com hash de senha.
- **Cardápio Dinâmico:** Visualização de produtos filtrados por categoria (*Burguer Clássico*, *Burguer Veggie*, *Snacks* e *Bebidas*).
- **Carrinho de Compras:** Adição de produtos, ajuste de quantidades, cálculo automático de subtotal/total e escolha da forma de pagamento (*PIX*, *Crédito* ou *Débito*).
- **Acompanhamento de Pedido:** Painel exclusivo (`dashboard_cliente.php`) para acompanhar o histórico e a evolução do pedido em tempo real.

### 🛠️ Módulo Administrativo / Cozinha
- **Gestão de Cardápio (`gerencia_produtos.php`):** CRUD de produtos comercializados com upload de imagem, descrição, preço e controle de disponibilidade.
- **Mapa de Pedidos (`dashboard_funcionario.php`):** Painel operacional para visualizar pedidos recebidos, consultar dados do cliente e atualizar o status do pedido (*Solicitado* → *Preparando* → *Pronto* → *A caminho* → *Entregue*).

---

## 🛠️ Tecnologias Utilizadas

- **Front-end:** HTML5, CSS3, JavaScript
- **Back-end:** PHP 8+ (Estruturado com PDO)
- **Banco de Dados:** MariaDB / MySQL
- **Ambiente Local:** XAMPP (Apache + MariaDB)

---

## 📂 Estrutura de Diretórios

```text
larica/
├── assets/
│   ├── css/
│   │   └── style.css               # Visual de todas as telas
│   ├── js/
│   │   └── script.js               # Máscaras de CPF, telefone e validação de senha ao cadastrar
│   └── img/                        # Fotos dos hambúrgueres, snacks e bebidas
├── includes/
│   ├── conexao.php                 # Conexão PDO com o MariaDB/MySQL
│   └── header.php                  # Gerencia o cabeçalho dinâmico conforme a sessão ativa
├── sql/
│   └── larica_burguer.sql          # Script de criação do banco, tabelas e carga inicial
├── index.php                       # Página inicial
├── sobre.php                       # História da Larica Burguer
├── contato.php                     # Formulário de contato (elogios, opiniões e reclamações)
├── login.php                       # Tela única de acesso (Cliente e Funcionário)
├── logout.php                      # Encerra a sessão do usuário ativo no sistema
├── cadastro.php                    # Cadastro público (apenas Clientes)
├── cardapio.php                    # Apresentação dos produtos com filtro por categoria
├── carrinho.php                    # Revisão e fechamento do pedido do Cliente
├── dashboard_cliente.php           # Painel e histórico de pedidos do Cliente
├── dashboard_funcionario.php       # Painel operacional (fila de pedidos)
├── gerencia_produtos.php           # CRUD de produtos (acesso exclusivo para Funcionário)
└── README.md                       # Documentação do projeto
```

---

## ⚙️ Como Executar o Projeto Localmente

Para rodar esta aplicação em sua máquina, você precisará do **XAMPP** (ou servidor PHP/MySQL equivalente) e do **Git** instalados.

### 1. Clonar o repositório
Abra o terminal dentro da pasta `htdocs` do seu XAMPP (ex: `C:\xampp\htdocs`) e execute:
```bash
git clone https://github.com
```
*(Dica: Renomeie a pasta clonada para `larica` para manter o caminho padrão).*

### 2. Iniciar os serviços
Abra o painel de controle do **XAMPP** e inicie os módulos **Apache** e **MySQL**.

### 3. Configurar o Banco de Dados
- Acesse `http://localhost/phpmyadmin` no navegador.
- Crie o banco de dados `larica_burguer` e importe/execute o arquivo SQL localizado na pasta `sql/` do projeto.
- Confira as credenciais de acesso em `includes/conexao.php` (padrão XAMPP: host `localhost`, usuário `root`, senha vazia).

### 4. Acessar o sistema
Abra o navegador e acesse:
```text
http://localhost/larica/
```

---

## 🔑 Credenciais para Teste

Para facilitar a avaliação do sistema (desde que a carga inicial do banco de dados tenha sido importada), utilize os perfis pré-cadastrados abaixo na tela de login (`login.php`):

| Perfil                    | E-mail                    | Senha     | Acesso Principal                                  |
| :------------------------:|:-------------------------:|:---------:|:-------------------------------------------------:|
| **Funcionário (Admin)**   | `admin@laricaburguer.com` | `12345`   | Fila de pedidos e CRUD de produtos                |
| **Cliente**               | `teste03@gmail.com`       | `12345`   | Cardápio, carrinho e acompanhamento de pedidos    |

---

## 👨‍💻 Autor

Desenvolvido por **Jefferson Dantas de Freitas**.

- **GitHub:** [@jeffdfreitas](https://github.com)
- **Repositório:** [projeto_pnt2_at1_at2](https://github.com/projeto_pnt2_at1_at2)

---

## 📄 Licença

Este projeto foi desenvolvido para fins acadêmicos e está sob a licença MIT.
