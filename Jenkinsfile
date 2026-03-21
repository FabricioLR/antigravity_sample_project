pipeline {
    agent any

    triggers {
        // Plugin GitHub para execução automática em push/webhook
        githubPush()
    }

    stages {
        stage('Testes Automatizados') {
            when {
                branch 'main'
            }
            agent {
                // Docker in Docker (DinD): Container privilegiado roda o seu próprio daemon docker
                docker {
                    image 'docker:dind'
                    args '--privileged'
                    reuseNode true
                }
            }
            steps {
                echo 'Executando testes automatizados do Web Storage...'
                sh 'chmod +x run-tests.sh'
                sh './run-tests.sh'
            }
        }

        stage('Deploy em Produção') {
            when {
                branch 'main'
            }
            agent {
                // Utilizando a estrutura de Docker in Docker para o Deploy
                docker {
                    image 'docker:dind'
                    args '--privileged'
                    reuseNode true
                }
            }
            steps {
                echo 'Testes aprovados! Realizando deploy da imagem de produção...'
                
                // Mecanismo de secret do Jenkins. Assumindo a criação de um credential tipo 'Secret file'
                withCredentials([file(credentialsId: 'web_storage_prod_env', variable: 'PROD_ENV_FILE')]) {
                    // Copiando o arquivo secret para a raiz do container de deploy como .env
                    sh 'cp $PROD_ENV_FILE .env'
                    
                    // Deploy de produção utilizando o template específico
                    sh 'docker compose -f docker-compose.prod.yml up --build -d'
                }
            }
        }
    }

    post {
        always {
            // Garante uma limpeza passiva dos containers de teste residuais
            sh 'docker compose -f docker-compose.test.yml down -v || true'
            
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
