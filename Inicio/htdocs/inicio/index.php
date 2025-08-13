<!DOCTYPE html>
<html>
<head>
    <title>Sistema SIAGE</title>
    <link rel="stylesheet" href="assets/estilo.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        h1 {
            margin-top: 40px;
            color: #333;
            font-weight: 600;
        }
        .modulos {
            display: flex;
            gap: 40px;
            margin-top: 60px;
            justify-content: center;
        }
        .modulo-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            box-shadow: 0 6px 24px rgba(0,0,0,0.15);
            border: none;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            position: relative;
        }
        .modulo-btn span {
            position: absolute;
            bottom: 12px;
            left: 0;
            width: 100%;
            text-align: center;
            color: #fff;
            font-weight: 600;
            font-size: 1.1em;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .modulo-btn.psicologa {
            background-image: url('../../img/Especialista.jpeg');
        }
        .modulo-btn.ninos {
            background-image: url('../../img/niños.jpeg');
        }
        .modulo-btn.padres {
            background-image: url('../../img/padres.jpeg');
        }
        .modulo-btn:hover {
            transform: scale(1.07);
            box-shadow: 0 12px 32px rgba(0,0,0,0.22);
        }
        @media (max-width: 600px) {
            .modulos {
                flex-direction: column;
                gap: 30px;
            }
        }
        .footer {
  background-color: #000;
  color: #fff;
  text-align: center;
  padding: 15px 10px;
  font-size: 0.95em;
  border-top: 3px solid var(--accent);
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
}
footer p {
  margin: 0;  /* Quita márgenes extra */
}
    </style>
</head>
<body>
    <h1>Bienvenido a ReSet</h1>
    <div class="modulos">
        <a href="psicologa.php" class="modulo-btn psicologa">
            <span>Psicóloga</span>
        </a>
        <a href="ninos.php" class="modulo-btn ninos">
            <span>Niños</span>
        </a>
        <a href="padres.php" class="modulo-btn padres">
            <span>Padres</span>
        </a>
    </div>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>
</body>
</html>