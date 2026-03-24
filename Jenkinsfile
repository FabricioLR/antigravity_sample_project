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
                    sh """
                    echo "TEST_DB_HOST=db_test" > .env
                    echo "TEST_DB_DATABASE=web_storage_test" >> .env
                    echo "TEST_DB_USERNAME=test_user" >> .env
                    echo "TEST_DB_PASSWORD=test_pass" >> .env
                    echo "TEST_POSTGRES_DB=web_storage_test" >> .env
                    echo "TEST_POSTGRES_USER=test_user" >> .env
                    echo "TEST_POSTGRES_PASSWORD=test_pass" >> .env
                    echo "STORAGE_TYPE=oci" >> .env
                    echo "STORAGE_ROOT=/var/www/html/storage" >> .env
                    """
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
                        string(credentialsId: 'web_storage_prod_db_pass', variable: 'WEB_STORAGE_PROD_DB_PASS'),
                        string(credentialsId: 'web_storage_oci_key_file', variable: 'OCI_KEY_FILE'),
                        string(credentialsId: 'web_storage_oci_tenancy_ocid', variable: 'OCI_TENANCY_OCID'),
                        string(credentialsId: 'web_storage_oci_user_ocid', variable: 'OCI_USER_OCID'),
                        string(credentialsId: 'web_storage_oci_fingerprint', variable: 'OCI_FINGERPRINT'),
                        string(credentialsId: 'web_storage_oci_region', variable: 'OCI_REGION'),
                        string(credentialsId: 'web_storage_oci_namespace', variable: 'OCI_NAMESPACE'),
                        string(credentialsId: 'web_storage_oci_bucket', variable: 'OCI_BUCKET')
                    ]) {
                        sh """
                        echo "PROD_DB_HOST=10.0.0.110" > .env
                        echo "PROD_DB_DATABASE=${WEB_STORAGE_PROD_DB_NAME}" >> .env
                        echo "PROD_DB_USERNAME=${WEB_STORAGE_PROD_DB_USER}" >> .env
                        echo "PROD_DB_PASSWORD=${WEB_STORAGE_PROD_DB_PASS}" >> .env
                        echo "PROD_POSTGRES_DB=${WEB_STORAGE_PROD_DB_NAME}" >> .env
                        echo "PROD_POSTGRES_USER=${WEB_STORAGE_PROD_DB_USER}" >> .env
                        echo "PROD_POSTGRES_PASSWORD=${WEB_STORAGE_PROD_DB_PASS}" >> .env
                        echo "STORAGE_TYPE=oci" >> .env
                        echo "STORAGE_ROOT=/var/www/html/storage" >> .env
                        
                        echo "OCI_KEY_FILE=${OCI_KEY_FILE}" >> .env
                        echo "OCI_TENANCY_OCID=${OCI_TENANCY_OCID}" >> .env
                        echo "OCI_USER_OCID=${OCI_USER_OCID}" >> .env
                        echo "OCI_FINGERPRINT=${OCI_FINGERPRINT}" >> .env
                        echo "OCI_REGION=${OCI_REGION}" >> .env
                        echo "OCI_NAMESPACE=${OCI_NAMESPACE}" >> .env
                        echo "OCI_BUCKET=${OCI_BUCKET}" >> .env
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
