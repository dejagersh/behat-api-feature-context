Feature: test feature


  Scenario:
    Given I have the payload
    """
      my payload
    """
    When I request "GET /weightlogs"
    Then the response status code should be 200