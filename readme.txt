=== Plugin Name ===
Moloni
Contributors: molonidevteam
Tags: Invoicing, Orders
Stable tag: 3.0.60
Tested up to: 5.8
Requires PHP: 5.6
Requires at least: 4.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Software de faturação inovador que se adapta ao seu negócio! Destinado a profissionais liberais, micro, pequenas e médias empresas. Sem investimento inicial, completo e intuitivo.

== Description ==
O Moloni é um inovador software de faturação e POS online que inclui acesso a inúmeras ferramentas úteis e funcionais que permitem a cada empresa gerir a sua faturação, controlar stocks, automatizar processos e emitir documentos de forma rápida, simples e intuitiva.

Certificado com o nº 1455 da Autoridade Tributária, o Moloni está sempre atualizado e de acordo com a lei em vigor!

== Através do plugin é possível:  ==
* Sincronizar artigos e stocks entre as duas plataformas
* Emissão automática ou manual de documentos
* Seleccionar o estado dos documentos emitidos
* Seleccionar de uma grande variedade de tipos de documentos
* Seleccionar o armazém de saída dos artigos
* Envio automático do documento para o cliente
* Criação automática de clientes e artigos
* Personalizar os seus detalhes de faturação
* Aceder aos documentos emitidos sem sair do Wordpress

Todo o suporte técnico e comercial dado aos utilizadores do plugin é prestado pela equipa de Apoio a Clientes do Moloni.

== Frequently Asked Questions ==

= Existe alguma versão paga do plugin? =
Não. O plugin foi desenvolvido e é disponibilizado de forma completamente gratuita pela equipa Moloni.

= Quanto terei que pagar pelo suporte? =
À semelhança do software Moloni, todo o suporte é completamente gratuito e prestado inteiramente pela nossa equipa de Apoio a Clientes do Moloni.

= Tenho dúvidas ou sugestões, quem posso contactar? =
Para qualquer duvida ou sugestão pode entrar em contacto connosco através do email apoio@moloni.com.

= Os documentos estão a ser emitidos sem contribuinte =
Por defeito, o WooCommerce não tem um campo de contribuinte, como tal, o que se costuma fazer é adicionar um plugin para adicionar o contribuinte ao cliente.

Estes plugins criam um custom_field associado à morada de facturação do cliente, como por exemplo `_billing_nif`.

Depois de ter um plugin para o contribuinte instalado, basta seleccionar nas configurações do plugin Moloni qual o `custom_field` que corresponde ao contribuinte do cliente.


== Installation ==
Este plugin pode ser instalado através de FTP ou utilizando o instalador de plugins do Wordpress.

Via FTP
1. Upload dos ficheiros do plugin para o diretório `/wp-content/plugins/moloni`
2. Ativar o plugin através da opção `Plugins` visível no WordPress

== Screenshots ==
1. Página principal onde pode emitir os seus documentos de encomendas pendentes
2. Todas as nossas configurações disponíveis para o plugin
3. Ferramentas de sincronização e consulta

== Upgrade Notice ==
= 3.0 =
Released plugin version 3.
New plugin version fully re-written

== changelog ==
= 3.0.60 =
* FIX: Correcção de impostos de taxas com valor reduzido

= 3.0.59 =
* FEATURE: Acrescentada, caso seja ativado nas definições do plugin, uma coluna na listagem de encomendas WooCommerce para descarregamento rápido de documentos.

= 3.0.58 =
* MINOR: Testado até à versão 5.8 do Wordpress
* MINOR: Testado até à versão 5.6.0 do WooCommerce
* FIX: Correção em encomendas com produtos devolvidos

= 3.0.57 =
* CHANGE: Alteração nas taxas de produtos compostos
* FIX: Corrigido de erro com métodos de entrega inválidos

= 3.0.56 =
* FEATURE: Adicionado filtro moloni_after_close_document após fecho de documentos
* FIX: Corrigido método de cálculo do valor de pagamento

= 3.0.55 =
* MINOR: Prevenção de alertas de constantes

= 3.0.54 =
* MINOR: Manter visibilidade do produto na atualização.

= 3.0.53 =
* FIX: Prevenção de criação de documentos repetidos (através de hooks)
* MINOR: Pesquisa de produtos retorna produtos inativos.

= 3.0.52 =
* FEATURE: Possibilidade de definir uma razão de isenção para vendas fora da Europa
* MINOR: Comparações rigorosa

= 3.0.51 =
* FIX: Alterada a forma como é definido o stock de um artigo durante a sincronização

= 3.0.49 =
* CHANGE: Alterada visibilidade de algumas propriedades para poderem ser alteradas nos filtros de criação de documentos

= 3.0.48 =
* FIX: Correção de erro no PHP 8.

= 3.0.47 =
* FEATURE: Adicionado filtro moloni_before_start_document no inicio do processo de criação de documento
* FEATURE: Adicionado filtro moloni_before_insert_document antes da criação do documento
* FEATURE: Adicionado filtro moloni_after_insert_document após da criação do documento

= 3.0.46 =
* MINOR: Testado até à versão 5.7 do Wordpress
* MINOR: Testado até à versão 5.1.0 do WooCommerce
* FIX: Correção de comportamento do plugin quando são feitos pedidos à API Rest do WooCommerce.

= 3.0.45 =
* MINOR: Testado até à versão 5.6.2 do Wordpress
* FIX: Correção de comportamento do plugin quando são feitos pedidos à API Rest do WooCommerce.

= 3.0.44 =
* MINOR: Testado até à versão 5.5.1 do Wordpress

= 3.0.43 =
* CHANGE: Colocação automática da taxa de IVA por defeito caso não tenha razão de isenção selecionada

= 3.0.42 =
* FEATURE: Adicionada opção para definir o estado do stock de um artigo quando o artigo é sincronizado com stock igual a zero
* FEATURE: Adicionada opção para atualização de stocks com base num armazém apenas
* FEATURE: Adicionada opção para escolher se o nome deverá ser usado para a referência do artigo ou não
* FEATURE: Adicionada a meta tag _ywbc_barcode_display_value para que possa ser usada como EAN
* FEATURE: Adicionado filtro moloni_admin_menu_permission para as permissões de administrador

= 3.0.41 =
* FIX: Erros de notificação na página de opções após primeiro login

= 3.0.40 =
* CHANGE: Na criação de um artigo, se não tiver referência, passou a ser usado o ID do artigo como base da referência
* FIX: Na actualização de artigos, passamos a limpar as taxas antes de actualizar, de forma a evitar taxas duplicadas

= 3.0.37 =
* FEATURE: Adicionada possibilidade de emissão de faturas pró-forma

= 3.0.34 =
* FEATURE: Adicionados Hooks moloni_after_order_shipping_setName e moloni_after_order_shipping_setSummary para que seja alterado o nom e resumo dos portes
* CHANGE: Solidificar o método de renovação das tokens com retry e log
* FIX: Tratar da codificação do nome das categorias em caracteres como &

= 3.0.33 =
* FEATURE: Método de expedição adicionado automaticamente

= 3.0.32 =
* FEATURE: Utilização de artigos compostos
* FIX: Verificação extra para prevenir preços negativos

= 3.0.31 =
* FIX: Remover criação de erro ao criar um documento quando o Hook corre no Front End

= 3.0.30 =
* CHANGE: Passou a sér possível emitir documentos diretamente da encomenda quando a mesma está no estado "wc-pending" e "wc-on-hold"
* FIX: Quando um artigo "filho" é atualizado no WooCommerce, passou também a correr a ação de atualização de artigos do Moloni

= 3.0.29 =
* FEATURE: Permitir a escolha entre criar documento quando a encomenda passa a "Completa" ou "Em Processamento"
* FEATURE: Permitir escolher o tipo de documento diretamente na página da encomenda
* CHANGE: A opção "Tem Stock" passa a ser controlada pela opção do WooCommerce "Gerir stock"
* CHANGE: Caso um artigo "Atributo" não tenha preço, deverá ser usado o preço do artigo "Pai"
* FIX: Remover as tags do nome do artigo

= 3.0.28 =
* CHANGE: Permitir sincronização de artigos com variações. Os artigos "Filhos" são criados individualmente no Moloni e com os dados correctos

= 3.0.27 =
* FEATURE: Adição de hook para a alteração do resumo do artigo
* FEATURE: A funcionalidade de inserir/atualizar artigos foi dividida em duas, uma própria para inserir, outra para actualizar
* FEATURE: Adicionada nova opção para usar os dados dos artigos que estão no Moloni (nome e resumo), caso já existam
* CHANGE: O campo EAN passou a não ser atualizado caso esteja vazio
* CHANGE: Validação correta das taxas/isenções

= 3.0.26 =
* FIX: Correção do carregamento de ficheiros .js e .css

= 3.0.25 =
* FIX: Correção da ordem das taxas

= 3.0.24 =
* FIX: Verificação da taxa nos artigos - Verificar se a taxa é do tipo IVA

= 3.0.22 =
* FEATURE: Criação de vários níveis de categorias e sub-categorias

= 3.0.2 =
* FEATURE: Inserção de métodos de pagamento
* FIX: Formulário de login na página do artigo

= 3.0.19 =
* FEATURE: Suporte a Orçamentos

= 3.0.18 =
* FEATURE: Suporte a Orçamentos

= 3.0.18 =
* CHANGE: Melhoria no cálculo das taxas de Portes
* FIX: Preços atualizados deverão ter em conta se os artigos têm IVA incluído ou não
* FIX: Strip Slashes da password

= 3.0.17 =
* FIX: Melhorias na validação dos códigos postais

= 3.0.16 =
* FIX: Alterações no câmbio

= 3.0.15 =
* FEATURE: Utilização de moedas de câmbio

= 3.0.13 =
* FEATURE: Paginação na listagem de encomendas pendentes

= 3.0.12 =
* CHANGE: Emissão de documentos em bulk
* FIX: Razões de isenção em taxas de 0%

= 3.0.11 =
* CHANGE: Consulta de logs
* CHANGE: Limpeza de logs
* FIX: Sincronização de stocks
