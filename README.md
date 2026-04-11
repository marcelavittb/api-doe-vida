CADASTRO 
TELA CADASTRE-SE  (API REGISTER)

Tela de Dados Pessoais (API REGISTER)
 
incluir senha e confirmar senha
incluir campos responsável - nome - cpf e data de nascimento do responsável
Esqueci a senha - API externa para enviar email com a senha atual do usuario

DOADOR- http://localhost:3000/dashboard/donor
Arrumar modelo dashboard para consumir dados das doações do usuário
Possível Agendar doação na tela de doador (API DE AGENDAMENTO)
Confirmar Agendamento 
Editar mais informações do perfil (endereço,cidade) e poder alterar senha 
(API - update/id) 
avatar usuário 

Criar coluna no banco ( tempo de restrição ou Tempo de doação) para validar o período de próxima disponibilidade de doação


FUNCIONARIO : http://localhost:3000/dashboard/staff

dashboard consumir do banco com consultas 

Confirmar/cancelar agendamentos (API Agendamento)

Abrir modal com informações da Doação (tipo sanguíneo do paciente, aptidao sanguínea, status doação, status doador (inapto, apto), Ml´s, bolsas

Cancelamento do agendamento (informar motivo (não comparecimento, não apto na entrevista, saude não elegível para doação)

Atualizar estoque (informar descrição do processo) - somar quantidade de ml de acordo com as bolsas

Buscar Doador (buscar somente DOADORES DO HEMOCENTRO , poder alterar o tipo sanguíneo, telefone e hemocentro, inativar ,  restringir usuário para doação ) API USERS PUT

Editar o Proprio cadastro  (informações do proprio funcionario) API USERS PUT


DIRETOR http://localhost:3000/dashboard/director

Remover Dashboard de Satisfação

Revisar Estatistica de Equipe ( se necessario remover) 

Editar informações do Usuario Funcionario ( Nome , cargo, telefone, status, permissoes ) API USERS PUT

Criar funcionario (senha, endereço, hemocentro id(fixo do atual)telefone,data de nasc, email),  cargo-  api/users/create 

Adicionar Coluna de criar/editar Permissoes (usar spatie permissons) 

Estoque - Emitir relação de doações realizadas no dia/semana/mes do tipo sanguineo selecionado

Relatorios: 
          TODOS GRAFICO DEVERAO SER EXPORTADOS EM PDF

relatorio mensal abrir modal com grafico de Torre, Linha
Relatorio de DOADORES: Ativos, doadores inaptos, motivos de inapividate, concluidas e etc (usar dashboard helen de referncia)
relatorio de estoque - Linha,Torre - Poder alterar tipos que deseja no relatorio
Relatorio de Desempenho - Mais realizaram cadastro e atendimento, satisfação e etc (senao remover o modal do grafico)

ADMINISTRADOR:
CRIA CAMPANHAS E DESIGNA HEMOCENTROS PARA ELA
EDITA HEMOCENTROS E STATUS
3. Pode criar doadores tambem
4. Edita permissoes e pode alterar permissoes de usuarios


HEMOCENTRO 
HORARIO DE FUNCIONAMENTO E DATAS DISPONIVEIS PARA AGENDAR
DOAÇÕES POR DIA E MAXIMO DE ESTOQUE ( ADMIN DETERMINA)
CADASTRAR NOVO HEMOCENTRO ADICIONAR CAMPOS CONSULTAR API-HEMOCENTRO 
ADICIONAR RESPONSAVEL PELO HEMOCENTRO (BANCO E JSON) 



 FINALIZAR EM OUTRA REUNIAO
AGENDAMENTO 
Arrumar reagendamento (excluir (mudar status para E)  o agendamento  que esta sendo remarcado e crie um novo

