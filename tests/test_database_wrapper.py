import unittest
from src.DatabaseWrapper import DatabaseWrapper

class TestDatabaseWrapper(unittest.TestCase):
    def setUp(self):
        self.config = {
            'host': 'localhost',
            'port': 5432,
            'user': 'user',
            'password': 'password',
            'database': 'test_db'
        }
        self.wrapper = DatabaseWrapper(self.config)

    def test_connect(self):
        # Test the connect method
        self.wrapper.connect()
        self.assertIsNotNone(self.wrapper.connection)

    def test_disconnect(self):
        # Test the disconnect method
        self.wrapper.connect()
        self.wrapper.disconnect()
        self.assertIsNone(self.wrapper.connection)

    def test_execute_query(self):
        # Test the execute_query method
        self.wrapper.connect()
        result = self.wrapper.execute_query("SELECT 1")
        self.assertEqual(result, 1)

    def test_fetch_results(self):
        # Test the fetch_results method
        self.wrapper.connect()
        self.wrapper.execute_query("SELECT 1")
        results = self.wrapper.fetch_results()
        self.assertEqual(results, [1])

if __name__ == '__main__':
    unittest.main()
