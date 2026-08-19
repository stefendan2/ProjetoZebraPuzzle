<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Amanda Imóveis - Home</title>
<link rel="stylesheet" href="public/assets/css/style.css">
<link rel="stylesheet"
	href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" type="text/css"
	href="public/assets/css/bootstrap.min.css">
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>

/* css do card de imóveis e botao */
.card .btn {
	border-radius: 30px;
	padding-top: 10px;
	padding-bottom: 10px;
	padding-left: 20px;
	padding-right: 20px;
	border-width: 2px;
	top: 0;
	position: relative;
	-webkit-transition: .3s all ease;
	-o-transition: .3s all ease;
	transition: .3s all ease;
}


/* css da search bar */
.search {
	position: relative;
}

.search input {
	height: 60px;
	text-indent: 25px;
}

.search input:focus {
	box-shadow: none;
}

.search .fa-search {
	position: absolute;
	top: 20px;
	left: 16px;
}

.search button {
	position: absolute;
	top: 5px;
	right: 5px;
	height: 50px;
	width: 110px;
	background: #08283B;
	border: #08283B;
	color: #fff;
	
}

.search button:hover {
	background: #fff;
	color: black;
	border: 1px solid #600202;
}

h1 {
	color: #e7e9ec;
	text-align: center;
	margin: 3rem 0;
	font-size: 3rem;
	font-weight: 300px;
}

.modal-dialog {
	position: relative;
	width: 22.875rem;
	min-height: 5rem;
	max-height: calc(100vh - 40px);
	background-color: #fff;
	border-radius: 0.75rem;
	overflow-y: auto;
}

.nav-tabs .nav-link:not(.active) {
	color: #000;
	background-color: transparent;
	border: none;
}

.msg {
	text-align: center;
	font-size: small;
	margin-top: 25px;
}

span a {
	text-decoration: none;
	font-weight: 700;
	color: #000;
	transition: .5s;
}

span a:hover {
	text-decoration: underline;
	color: #000;
}

a {
text-decoration: none;
}
.im-dois{
   background: rgba(255, 255, 255, 1);
	width: 100%;
	padding: 20px;
	margin: 0 auto;
	position: relative;
	-webkit-border-radius: 10px;
	-moz-border-radius: 10px;
	border-radius: 10px;
	-webkit-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
	-moz-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
	box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
}

</style>
</head>
<body>


<!-- menu offcanvas -->
	<nav class="navbar bg-white shadow-sm fixed-top p-2 navbar-dark">
  <div class="container-fluid">
    <img class="img2" src="public/assets/images/logotxt.png">
   <img class=" navbar-brand img ms-auto" src="public/assets/images/imglogo.png">
   <div class="p-3 ms-auto">
				<button type="button" class="btn" data-bs-toggle="modal"
					data-bs-target="#myModal" 
					style="border-color: #08283B;  border-width: 2px; font-size: 110%; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;"
					>Entrar</button>
			</div>
    <button class="navbar-toggler" type="button" style="background: #08283B;" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
      <h5 class="offcanvas-title fw-bold text-dark" style="color: #fff;"
						id="offcanvasDarkNavbarLabel">Amanda Imóveis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
						<li class="nav-item"><a class="nav-link text-dark" href="#"><i
								class="bi bi-house-door-fill"></i> Home</a></li>
						<li class="nav-item"><a class="nav-link text-dark" href="imoveis">
								<i class="bi bi-houses-fill"></i> Imóveis
						</a></li>
						<li class="nav-item"><a class="nav-link text-dark"
							href="anunciarimovel"><i class="bi bi-house-check-fill"></i>
								Cadastrar Imóvel</a></li>
						<li class="nav-item"><a class="nav-link text-dark" href="contato"><i
								class="bi bi-envelope-fill"></i> Contato</a></li>
						<li class="nav-item"><a class="nav-link text-dark" href="sobrenos"><i
								class="bi bi-people-fill"></i> A Imobiliária</a></li>
					</ul>
      </div>
    </div>
  </div>
</nav>
	<!-- fim menu offcanvas -->
	<!--  modal login -->
	<div class="modal fade" id="myModal">
		<div class="modal-dialog .modal-fullscreen-sm-down">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Amanda Imóveis -
						Login</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<!-- Modal body -->
				<div class="modal-body">
					<ul class="nav nav-tabs" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link active" id="home-tab"
								data-bs-toggle="tab" data-bs-target="#home" type="button"
								role="tab" aria-controls="home" aria-selected="true">Entrar</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="profile-tab" data-bs-toggle="tab"
								data-bs-target="#profile" type="button" role="tab"
								aria-controls="profile" aria-selected="false">Cadastrar</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="contact-tab" data-bs-toggle="tab"
								data-bs-target="#contact" type="button" role="tab"
								aria-controls="contact" aria-selected="false">Recuperar senha</button>
						</li>
					</ul>
					<!--  Entrar -->
					<div class="tab-content" id="myTabContent">
						<div class="tab-pane fade show active" id="home" role="tabpanel"
							aria-labelledby="LoginTab">
							<form id="LoginTab">
								<div class="form-group">
									<div class="form-floating mb-3 mt-3">
										<input type="email" class="form-control rounded-3"
											id="floatingInput" placeholder="name@example.com"> <label
											for="floatingInput">Email: </label>
									</div>
									<div class="form-floating">
										<input type="password" class="form-control rounded-3"
											id="floatingPassword" placeholder="Password"> <label
											for="floatingPassword">Senha:</label>
									</div>
									<button type="submit" class="btn btn-block mt-3"
										style="background-color: #08283B; color: #fff; width: 100%;">Entrar</button>
									<div class="msg">
										<span><a href="#">Esqueceu sua senha?</a></span>
									</div>
								</div>
							</form>

						</div>

						<!--  Fim Entrar -->

						<div class="tab-pane fade" id="profile" role="tabpanel"
							aria-labelledby="CadastroTab">

							<form id="CadTab">
								<div class="form-floating mb-3 mt-3">
									<input type="text" class="form-control rounded-3"
										id="floatingInputN" placeholder="name"> <label
										for="floatingInput">Nome: </label>
								</div>

								<div class="form-floating mb-3 mt-3">
									<input type="email" class="form-control rounded-3"
										id="floatingInputE" placeholder="name@example.com"> <label
										for="floatingInput">Email: </label>
								</div>
								<div class="form-floating mt-3">
									<input type="password" class="form-control rounded-3"
										id="floatingPassword2" placeholder="Password"> <label
										for="floatingPassword">Senha:</label>
								</div>
								<div class="form-floating mt-3">
									<input type="password" class="form-control rounded-3"
										id="floatingPassword3" placeholder="Password"> <label
										for="floatingPassword">Repetir senha:</label>
								</div>
								<button type="submit" class="btn btn-block mt-3"
									style="background-color: #08283B; color: #fff; width: 100%">Entrar</button>
							</form>
						</div>
						<div class="tab-pane fade" id="contact" role="tabpanel"
							aria-labelledby="LembrarTab">
							<h5 class="mt-3">Redefinir senha</h5>
							<p>Insira o seu endereço de e-mail abaixo. Iremos enviar um
								código para o seu e-mail, que permitirá que você redefina a sua
								senha.</p>
							<form id="LembrarTab">
								<div class="form-floating mb-3 mt-3">
									<input type="email" class="form-control rounded-3"
										id="floatingInputEm" placeholder="name@example.com"> <label
										for="floatingInput">Email: </label>
								</div>
								<button type="submit" class="btn btn-block mt-3"
									style="background-color: #08283B; color: #fff; width: 100%">Enviar</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- fim modal login -->

	<!-- inicio video background -->
	<div class="position-relative overflow-hidden">
		<div class="d-flex min-vh-100" lc-helper="video-bg">
		
			<video
				style="z-index: 1; object-fit: cover; object-position: 50% 50%; filter: brightness(0.5)"
				class="position-absolute w-100 min-vh-100" autoplay="" preload=""
				muted="" loop="" playsinline="">
				<source src="public/assets/images/videosp.mp4" type="video/mp4">
			</video>
			<div style="z-index: 2"
				class="align-self-center text-center text-light col-md-8 offset-md-2">
				<div class="lc-block mb-4">
					<div>
						<h1 style="color: white; text-shadow: 1px 2px  black;">Seu imóvel está aqui!</h1>
					</div>
				</div>
				<div class="lc-block">
					<div>
						<p class="fs-4" style="color: white; text-shadow: 1px 2px black;">Encontre aqui o imóvel dos seus sonhos</p>
					</div>
				</div>
				<div class="lc-block">
					<div
						class="row height d-flex justify-content-center align-items-center">
						<div class="col-md-8">
							<div class="search">
								<i class="fa fa-search"></i> <input type="text"
									class="form-control"
									placeholder="Pesquise o tipo de imóvel que você deseja">
								<button class="btn">Buscar</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- fim video background -->

<!-- cards de cadastrar imovel e encomenda -->
<section style="background-color: #eee;">
<div class="container">
<div class="row justify-content-center mb-4" >
  <div class="col-sm-4 mt-4 mb-4">
    <div class="card im-dois">
      <div class="card-body">
      <i class="bi bi-building-fill-gear"style="font-size: 40px;"></i>
        <h5 class="card-title"style="color: #000;">Seu Imóvel à Medida</h5>
        <p class="card-text">Descreva a propriedade que você está procurando e nós o notificaremos assim que a encontrarmos</p>
        <a href="contato" class="btn"style="color: #08283B;">Encomende seu Imóvel <i class="bi bi-arrow-right"></i></a>
        <hr style="border-top: 2px solid black;">
      </div>
    </div>
  </div>
  <div class="col-sm-4 mt-4">
    <div class="card im-dois">
      <div class="card-body">
      <i class="bi bi-building-fill-up"style="font-size: 40px;"></i>
        <h5 class="card-title"style="color: #000;">Cadastre seu imóvel</h5><br>
        <p class="card-text">Anuncie Conosco! Descobriremos a Melhor Oferta para Você</p>
        <a href="anunciarimovel" class="btn"style="color: #08283B;">Cadastre seu Imóvel <i class="bi bi-arrow-right"></i></a>
         <hr style="border-top: 2px solid black;">
      </div>
    </div>
  </div>
</div>
</div>
	<!-- fim de cards de cadastrar imovel e encomenda -->
  </section>
  <!-- inicio de imoveis para locacao -->
     <div class="container py-5">
        <div class="row mb-4">
            <div class="lc-block text-center">
                <h3>Principais Imóveis para locação</h3>
    <div class="d-grid gap-2 d-md-block">
  <button class="btn dois" type="button"style="border-color: black;
    border-radius: 5rem;  font-size: 0.870rem;">Destaques</button>
  <a href="imoveis" class="btn" tabindex="-1" role="button" aria-disabled="true"style="border: 2px solid transparent;
   font-weight: bold;color: #241818;">Ver mais</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/itanhaem.png" width="1080" height="768"></a>
                </div>
                <!-- /lc-block -->
            </div>
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/bambi4.jpg" width="1080" height="768"></a>
                </div>
                <!-- /lc-block -->
            </div>
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/casa.jpg" width="1080" height="768"></a>
                </div>
                </div>
                </div>
                </div>
                </div>
	<!-- fim de imoveis para locação -->
	
	  <!-- inicio de imoveis para venda -->
     <div class="container py-5">
        <div class="row mb-4">
            <div class="lc-block text-center">
                <h3>Principais Imóveis para venda</h3>
    <div class="d-grid gap-2 d-md-block">
  <button class="btn dois" type="button"style="border-color: black;
    border-radius: 5rem;  font-size: 0.870rem;">Destaques</button>
  <a href="imoveis" class="btn" tabindex="-1" role="button" aria-disabled="true"style="border: 2px solid transparent;
   font-weight: bold;color: #241818;">Ver mais</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/terreno-itanhaem.jpg" width="1080" height="768"></a>
                </div>
                <!-- /lc-block -->
            </div>
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/terrenobambi.jpg" width="1080" height="768"></a>
                </div>
                <!-- /lc-block -->
            </div>
            <div class="col-md-6 col-lg-4 g-4">
                <div class="lc-block">
                    <a href="#"><img class="img-fluid" src="public/assets/images/casa.jpg" width="1080" height="768"></a>
                </div>
                </div>
                </div>
                </div>
                </div>
                

	<!-- fim de imoveis para locação -->

<!--  footer -->

 
 <div class="mt-5 pt-5 pb-5 footer" style="background-color:#141414">
<div class="container">
  <div class="row">
    <div class="col-sm">
         <img id="img3" src="public/assets/images/logobranco.png">
          <h6 style="font-size: 100%;" class="pr-5 text-white">Sua casa dos sonhos está a um passo de distância. Conte conosco para encontrar o lar perfeito. Sua satisfação é a nossa prioridade. </h6>
      <p><a href="#"><i class="fa fa-facebook-square mr-1"></i></a><a href="#"><i class="fa fa-linkedin-square"></i></a></p>
    </div>
    <div class="col-sm text-white mt-3">
      <h4 class="mt-lg-0 mt-sm-4">Localização</h4><br>
      <h5 style="font-size: 115%;">Amanda Barbosa dos Santos Imobiliária</h5>
      <h5 style="font-size: 115%;">(11) 98328-1626</h5>
      <h5 style="font-size: 115%;">B.s.Amanda@bol.com.br</h5>
    </div>
    <div class="col-sm mt-3">
<div id="contact" class="map">
<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3661.660504595074!2d-46.3914681!3d-23.4004959!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce884f63a47bad%3A0xdbe7735e1d239e49!2sAv.%20Serra%20da%20Mantiqueira%2C%20310%20-%20Vila%20Carmela%20I%2C%20Guarulhos%20-%20SP%2C%2007178-540!5e0!3m2!1spt-BR!2sbr!4v1700695884711!5m2!1spt-BR!2sbr" width="350" height="150" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>    <br />
    <small>
        <a href="https://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=Twitter,+Inc.,+Market+Street,+San+Francisco,+CA&amp;aq=0&amp;oq=twitter&amp;sll=28.659344,-81.187888&amp;sspn=0.128789,0.264187&amp;ie=UTF8&amp;hq=Twitter,+Inc.,+Market+Street,+San+Francisco,+CA&amp;t=m&amp;z=15&amp;iwloc=A"></a>
    </small>
</div>

    </div>    
    </div>
 <div class="row mt-5 justify-content-center">
    <div class="col text-center">
        <p class=""><small class="text-white">© 2023 Copyright:
            <a class="text-white" href="https://hostdeprojetosdoifsp.gru.br/starbridge/home.php">StarBridge</a></small></p>
    </div>
</div>
 
  </div>
</div>

  <!--  fim footer -->
	
  
</body>
</html>
