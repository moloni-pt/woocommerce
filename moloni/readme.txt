=== Plugin Name ===
Moloni
Contributors: molonidevteam
Tags: Invoicing, Orders
Stable tag: 3.0.32
Tested up to: 5.4
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

== Changelog ==
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
