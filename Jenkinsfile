pipeline {
    agent any

    triggers {
        githubPush()
    }

    stages {
        stage('Testes Automatizados') {
            steps {
                script {
                    echo 'Execução do PHPUnit...'
                    sh './run-tests.sh'
                }
            }
        }

        stage('Build Imagem de Produção') {
            steps {
                script {
                    echo 'Build da imagem...'
                    sh 'docker compose -f docker-compose.prod.yml build';
                }
            }
        }

        stage('Deploy em Produção') {
            steps {
                script {
                    echo 'Realizando deploy usando containers independentes via shell puro...'
                    
                    withCredentials([file(credentialsId: 'web_storage_prod_env', variable: 'PROD_ENV_FILE')]) {
                        sh '''
                        cp $PROD_ENV_FILE .env
                        docker compose -f docker-compose.prod.yml down
                        docker compose -f docker-compose.prod.yml up -d
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            echo 'Limpando o ambiente temporário...'
            cleanWs()
        }
    }
}
