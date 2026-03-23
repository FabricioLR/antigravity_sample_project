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
                    
                    withCredentials([
                        string(credentialsId: 'web_storage_prod_db_name', variable: 'WEB_STORAGE_PROD_DB_NAME'),
                        string(credentialsId: 'web_storage_prod_db_user', variable: 'WEB_STORAGE_PROD_DB_USER'),
                        string(credentialsId: 'web_storage_prod_db_pass', variable: 'WEB_STORAGE_PROD_DB_PASS')
                    ]) {
                        sh """
                        echo "WEB_STORAGE_PROD_DB_NAME=${WEB_STORAGE_PROD_DB_NAME}" > .env
                        echo "WEB_STORAGE_PROD_DB_USER=${WEB_STORAGE_PROD_DB_USER}" >> .env
                        echo "WEB_STORAGE_PROD_DB_PASS=${WEB_STORAGE_PROD_DB_PASS}" >> .env
                        """

                        sh '''
                        
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
