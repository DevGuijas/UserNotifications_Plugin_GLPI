# Sino de notificações para GLPI 11 (1.0.12)

Plugin para GLPI 11.0.1+ que adiciona um sino imediatamente à esquerda do menu de perfil. Ele exibe atualizações recebidas pelos usuários atribuídos ao chamado, novas atribuições e solicitações de aprovação e menções nativas do editor do GLPI.

## Instalação

1. Copie a pasta `usernotifications` para `<glpi>/plugins/usernotifications`.
2. Em **Configuração > Plugins**, instale e ative **Sino de notificações**.
3. Atualize a página do GLPI.

As notificações são separadas por usuário, o contador mostra somente as não lidas e o botão **Marcar notificações como lidas** as mantém em cinza. Registros com mais de 30 dias são removidos automaticamente no carregamento do feed.

As aprovações pendentes e as menções nativas existentes nos últimos 30 dias são importadas quando o usuário abre o sino. Para menções, o plug-in usa os atributos nativos data-user-mention gravados pelo editor de texto rico do GLPI; não interpreta texto que apenas se pareça com uma arroba.