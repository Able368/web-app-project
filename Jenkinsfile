pipeline {
  agent any

  environment {
    // If you have NODE_VERSION tool configured in Jenkins, use it; otherwise agent needs node installed
    NODE_ENV = 'test'
  }

  stages {
    stage('Checkout') {
      steps {
        // checkout the repo that contains this Jenkinsfile
        checkout scm
      }
    }

    stage('Install') {
      steps {
        // install dependencies
        sh 'npm ci'   // or 'npm install' if package-lock.json not present
      }
    }

    stage('Build') {
      steps {
        // if you have a build step e.g., transpile
        // sh 'npm run build'
        echo 'No build step configured; skip'
      }
    }

    stage('Test') {
      steps {
        // ensure reports directory exists (jest will create it)
        sh 'mkdir -p reports'
        // run tests, jest configured to output JUnit XML (see package.json or jest.config.js)
        sh 'npm test'
      }

      post {
        always {
          // publish test results (JUnit XML)
          junit allowEmptyResults: false, testResults: 'reports/*.xml'

          // archive raw reports just in case
          archiveArtifacts artifacts: 'reports/*.xml', fingerprint: true
        }
      }
    }
  }

  post {
    success {
      echo "Pipeline succeeded"
    }
    failure {
      echo "Pipeline failed"
    }
    always {
      // optional: cleanup
      sh 'ls -la'
    }
  }
}
