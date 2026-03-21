pipeline {
    agent any

    triggers {
        // Plugin GitHub para execução automática em push/webhook
        githubPush()
    }

    stages {
        stage('Testes Automatizados') {
            when {
                branch 'master'
            }
            steps {
                echo 'Executando testes automatizados do Web Storage...'
                sh 'chmod +x run-tests.sh'
                sh './run-tests.sh'
            }
        }

        stage('Deploy em Produção') {
            when {
                branch 'master'
            }
            steps {
                echo 'Testes aprovados! Realizando deploy da imagem de produção...'
                
                // Mecanismo de secret do Jenkins. Assumindo a criação de um credential tipo 'Secret file'
                withCredentials([file(credentialsId: 'web_storage_prod_env', variable: 'PROD_ENV_FILE')]) {
                    // Copiando o arquivo secret para a raiz do container de deploy como .env
                    sh 'cp $PROD_ENV_FILE .env'
                    
                    // Deploy de produção utilizando o template específico
                    sh 'docker compose -f docker-compose.prod.yml up --build -d || docker-compose -f docker-compose.prod.yml up --build -d'
                }
            }
        }
    }

    post {
        always {
            // Garante uma limpeza passiva dos containers de teste residuais
            sh 'docker compose -f docker-compose.test.yml down -v || docker-compose -f docker-compose.test.yml down -v || true'
            
            // Apaga todos os arquivos do workspace antes de encerrar
            cleanWs()
        }
        success {
            echo 'Pipeline finalizada com sucesso! A versão de produção está no ar.'
        }
        failure {
            echo 'Falha detectada durante a pipeline. Nenhum deploy foi realizado.'
        }
    }
}
