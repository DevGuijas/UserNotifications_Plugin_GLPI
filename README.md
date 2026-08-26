# Sino de notificações para GLPI 11

Plugin para GLPI 11.0.1+ que adiciona um sino imediatamente à esquerda do menu de perfil. Ele exibe atualizações recebidas pelos usuários atribuídos ao chamado, novas atribuições e solicitações de aprovação.

## Instalação

1. Copie a pasta `usernotifications` para `<glpi>/plugins/usernotifications`.
2. Em **Configuração > Plugins**, instale e ative **Sino de notificações**.
3. Atualize a página do GLPI.

As notificações são separadas por usuário, o contador mostra somente as não lidas e o botão **Marcar notificações como lidas** as mantém em cinza. Registros com mais de 30 dias são removidos automaticamente no carregamento do feed.

As aprovações pendentes já existentes são importadas quando o aprovador abre o sino; eventos de acompanhamento, tarefa, atribuição e validação posteriores à ativação são gravados no momento em que ocorrem.