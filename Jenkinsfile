pipeline {
  agent any

  stages {
    stage('Checkout') {
      steps { checkout scm }
    }

    stage('Install') {
      steps {
        sh 'npm ci || npm install'
      }
    }

    stage('Test') {
      steps {
        sh 'mkdir -p reports'
        sh 'npm test'
      }
      post {
        always {
          // Publish JUnit-compatible test report(s)
          junit allowEmptyResults: false, testResults: 'reports/*.xml'
          // Optionally archive the raw xml
          archiveArtifacts artifacts: 'reports/*.xml', fingerprint: true
        }
      }
    }
  }

  post {
    success { echo "Pipeline succeeded" }
    failure { echo "Pipeline failed — check test results" }
  }
}
