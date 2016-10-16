Feature: test feature


  Scenario:
    Given I have the payload
    """
      my payload
    """
    When I request "GET /weightlogs"
    Then the response status code should be 200
    And the "weightlogs" property should be an array
    And scope into the "weightlogs" property
    And the "0.weight" property should be a string equalling "78.8"