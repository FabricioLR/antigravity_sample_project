pipeline {
    agent any

    triggers {
        githubPush()
    }

    stages {
        stage('Testes Automatizados') {
            when {
                branch 'master'
            }
            steps {
                script {
                    echo 'Criando rede isolada e subindo banco de teste nativo (sem compose)...'
                    sh 'docker network create test_net || true'
                    sh 'docker rm -f web_storage_db_test || true'
                    
                    // Inicializa DB de teste na porta pedida 5433
                    sh 'docker run -d --name web_storage_db_test --network test_net -e POSTGRES_DB=web_storage_test -e POSTGRES_USER=test_user -e POSTGRES_PASSWORD=test_pass postgres:15-alpine postgres -p 5433'
                    
                    echo 'Aguardando inicialização do banco...'
                    sh 'sleep 15'
                    
                    echo 'Build da imagem de teste e execução do PHPUnit...'
                    def testImg = docker.build("web_storage-test", "-f docker/php/Dockerfile.dev .")
                    
                    testImg.inside("--network test_net -e DB_CONNECTION=pgsql -e DB_HOST=web_storage_db_test -e DB_PORT=5433 -e DB_DATABASE=web_storage_test -e DB_USERNAME=test_user -e DB_PASSWORD=test_pass") {
                        sh 'composer install'
                        sh 'vendor/bin/phpunit --colors=always'
                    }
                }
            }
        }

        stage('Build Imagens de Produção') {
            when {
                branch 'master'
            }
            steps {
                script {
                    echo 'Build das imagens refatorado para Docker DSL puro...'
                    docker.build("web_storage-prod", "-f docker/php/Dockerfile.prod .")
                    docker.build("web_storage-nginx-prod", "-f docker/nginx/Dockerfile.prod .")
                }
            }
        }

        stage('Deploy em Produção') {
            when {
                branch 'master'
            }
            steps {
                script {
                    echo 'Realizando deploy usando containers independentes via shell puro...'
                    
                    withCredentials([file(credentialsId: 'web_storage_prod_env', variable: 'PROD_ENV_FILE')]) {
                        sh 'cp $PROD_ENV_FILE .env'
                        
                        sh '''
                        docker network create prod_net || true
                        
                        docker stop web_storage_nginx_prod || true
                        docker rm web_storage_nginx_prod || true
                        docker stop web_storage_app_prod || true
                        docker rm web_storage_app_prod || true
                        
                        docker run -d --name web_storage_app_prod \\
                            --restart always \\
                            --network prod_net \\
                            --env-file .env \\
                            -v prod_storage:/var/www/html/storage \\
                            web_storage-prod:latest
                            
                        docker run -d --name web_storage_nginx_prod \\
                            --restart always \\
                            --network prod_net \\
                            -p 1234:1234 \\
                            web_storage-nginx-prod:latest
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            echo 'Limpando o ambiente temporário...'
            sh 'docker rm -f web_storage_db_test || true'
            sh 'docker rmi web_storage-test:latest || true'
            cleanWs()
        }
    }
}
