<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="mb-3">Confirmação diária</h1>
        <p class="text-secondary">Digite os seis caracteres da imagem. O código vale por 5 minutos.</p>

        <form method="post" action="<?= e(url('/login/captcha')) ?>" class="card card-body">
            <?= csrf_campo() ?>
            <div class="captcha-box mb-3">
                <img src="<?= e(url('/captcha/imagem')) ?>" width="220" height="70"
                     alt="Imagem do captcha diário">
            </div>
            <div class="mb-3">
                <label class="form-label" for="captcha">Código da imagem</label>
                <input class="form-control text-uppercase" id="captcha" name="captcha"
                       maxlength="6" required autocomplete="off" spellcheck="false">
            </div>
            <button type="submit" class="btn btn-primary">Validar e entrar</button>
            <a class="mt-3" href="<?= e(url('/login/captcha')) ?>">Gerar outra imagem</a>
        </form>
    </div>
</div>

