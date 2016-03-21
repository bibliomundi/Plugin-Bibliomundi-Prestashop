# Plugin-Bibliomundi-Prestashop

Módulo de integração dos Ebooks da Distribuidora de Livros Digitais <a href="http://www.bibliomundi.com.br" target="blank">Bibliomundi</a> para a plataforma de ecommerce Prestashop.

#Versão

1.0

#Requerimentos

<a href="https://www.prestashop.com" target="blank">Prestashop</a> na versão 1.6 ou maior. <br />
<a href="http://php.net" target="blank">PHP</a> na versão 5.4 ou maior. <br />
Extensão <a href="http://php.net/manual/pt_BR/book.mcrypt.php" target="blank">mcrypt</a> do PHP

#Instalação

Baixe o arquivo e renomei a pasta dentro do zip para "bibliomundi"(NECESSÁRIO) e na aba de criação de módulos do Prestahop faça o upload. Automaticamentne o nosso Módulo aparecerá em sua lista. Clique em instalar. Um alerta será exibido. Apenas clique em “prosseguir com a instalação”.

#Configurando o Módulo

Após instalar o Módulo com sucesso clique em configurar. Na tela inicial você terá apenas que escolher como deseja que os autores dos nossos ebooks sejam exibidos em sua loja. Escolha a opção que mais lhe agrada e clique em prosseguir.

#Imortando os Ebooks

Informe a chave e a senha que enviamos para você, escolha a o tipo da operação e o ambiente e clique em importar. 
Atenção. O tempo da importação irá variar de acordo com vários fatores, tais como a  velocidade do seu servidor e da conexão de sua internet!

#Atualizações Diárias

Você deve realizar a rotina diariamente a partir de 01:00 UTC−3 no arquivo cron.php que se encontra no diretório modules/PluginBibliomundiPrestashop

Rotina diária:

IMPORTANTE: Sua integração deve tratar os novos dados a partir desse horário para que os ebooks não sejam disponibilizados com dados defasados, evitando assim erro na venda.

#Observações

Não se esqueça de renomear a pasta, que se encontra na raiz do zip, para "bibliomundi". Sem isso você não conseguirá instalar o módulo. <br />
Após desinstalar o nosso módulo todos os nossos ebooks serão removidos de sua lista de produtos, bem como suas respectivas categorias, características e etiquetas.

#Faq

Após a instalação do módulo os meus produtos não estão sendo pesquisáveis em minha loja, por quê?. <br /> <br />
R. Na administração, navegue até Preferências > Buscar e clique em Reconstruir indíces. <br /> <br />
Por que os produtos da Bibliomundi aparecem como "sem estoque" ? <br /> <br />
R. Não há limitações de unidade para os nossos ebooks e no Prestashop não existe uma maneira nativa de configurar isto, logo, foi necessário alguns ajustes. Simplesmente ignore está mensagem. Não se preocupe.
