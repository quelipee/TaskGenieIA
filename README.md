# TaskGenie - IA para Respostas de Atividades

**TaskGenie** é um script baseado em inteligência artificial (IA) desenvolvido para automatizar a resposta a atividades e tarefas de forma eficiente e inteligente. Usando técnicas de processamento de linguagem natural, o script responde de maneira contextualizada a diferentes tipos de atividades que são fornecidas, proporcionando uma experiência mais ágil e otimizada.

## Funcionalidades

- **Respostas Inteligentes**: Utiliza IA para gerar respostas automatizadas com base nas atividades fornecidas.
- **Customização**: O sistema pode ser configurado para responder de acordo com diferentes padrões de tarefa ou tipos de questionamento.
- **Fácil Integração**: Pode ser facilmente integrado a outros sistemas ou fluxos de trabalho.

## Tecnologias Utilizadas

- **Linguagens**: PHP (Utilizando o Framework Laravel)
- **IA**: gemini
- **Bibliotecas**:
    - `openai` (para integração com o modelo gemini)
    - `requests` (para integração com APIs externas, caso necessário)

## Como Usar

### 1. Instalação

Clone o repositório para o seu ambiente local:

```bash
https://github.com/quelipee/TaskGenie.git
```
### 2. Configuração do arquivo .env
```bash
APP_USER_UNINTER_AUTH=INSIRA_SUA_KEY_AQUI
GEMINI_API_KEY=INSIRA_SUA_KEY_AQUI
APP_UNINTER_LOGIN=LOGIN_UNINTER
APP_UNINTER_PASSWORD=PASSWORD_UNINTER
```

## COMO FUNCIONA
- ### via comando
```bash
php artisan app:uninter
```
- ### utilizando o arquivo .bat (possui do projeto)
![alt text](image.png)

### 1. TELA DE MATERIAS
![img.png](img.png)

### 2. TELA DE ATIVIDADES DA MATERIA

![img_1.png](img_1.png)

### 3. RESOLVENDO A QUESTAO
![img_2.png](img_2.png)
